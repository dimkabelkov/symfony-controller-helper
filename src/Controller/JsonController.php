<?php

namespace Dimkabelkov\ControllerHelper\Controller;

use Dimkabelkov\ControllerHelper\Exception\ValidationParamsErrorException;
use Dimkabelkov\ControllerHelper\Type\AbstractType;
use Dimkabelkov\ControllerHelper\Type\Table\PagingType;
use Dimkabelkov\ControllerHelper\Type\Table\SortType;
use Dimkabelkov\CriteriaHelper\Exception\InvalidComparisonException;
use Dimkabelkov\CriteriaHelper\Exception\InvalidCriteriaException;
use Dimkabelkov\CriteriaHelper\Exception\OrderDirectionException;
use Dimkabelkov\CriteriaHelper\Query\QueryResult;
use Dimkabelkov\CriteriaHelper\Repository\AbstractRepository;
use Doctrine\ORM\NonUniqueResultException;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use ReflectionObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class JsonController extends AbstractController
{
    public const UUID_PATTERN = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/i';

    protected FormFactoryInterface $formFactory;

    /** @var SerializerInterface */
    protected SerializerInterface $serializer;

    /** @var Request|null */
    protected ?Request $request;

    /** @var ValidatorInterface $validator */
    protected ValidatorInterface $validator;

    /**
     * JsonController constructor.
     *
     * @param FormFactoryInterface $formFactory
     * @param SerializerInterface $serializer
     * @param RequestStack $requestStack
     * @param ValidatorInterface $validator
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        SerializerInterface $serializer,
        RequestStack $requestStack,
        ValidatorInterface $validator
    )
    {
        $this->formFactory = $formFactory;
        $this->serializer  = $serializer;
        $this->validator = $validator;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param QueryResult $queryResult
     *
     * @return JsonResponse
     */
    public function queryResult(QueryResult $queryResult): JsonResponse
    {
        $context = new SerializationContext();
        $context->setSerializeNull(true);

        $items = [];
        foreach ($queryResult->getItems() as $item) {
            $items[] = $this->serializer->toArray($item, $context);
        }

        return $this->result([
            'items' => $items,
            'prev' => $queryResult->getPrev(),
            'next' => $queryResult->getNext(),
            'count' => $queryResult->getCount()
        ]);
    }

    /**
     * @param $json
     *
     * @return JsonResponse
     */
    public function result($json): JsonResponse
    {
        return $this->json([
            'result' => $json
        ]);
    }

    /**
     * @return PagingType
     */
    public function getPagingType(): PagingType
    {
        $pagingType = new PagingType();

        $pagingForm = $this->formFactory
            ->createNamedBuilder('paging', PagingType::class, $pagingType)
            ->add('skip')
            ->add('limit')
            ->setMethod('GET')
            ->getForm();

        $pagingForm->handleRequest($this->request);

        return $pagingType;
    }

    /**
     * @return SortType
     */
    public function getSort(): SortType
    {
        $sortType = new SortType();

        $sortForm = $this->formFactory
            ->createNamedBuilder('sort', SortType::class, $sortType)
            ->add('sort')
            ->add('by')
            ->setMethod('GET')
            ->getForm();

        $sortForm->handleRequest($this->request);

        return $sortType;
    }

    /**
     * @param AbstractType $type
     * @param bool $allowExtraFields
     * @param array $params
     *
     * @throws ValidationParamsErrorException
     * @throws ReflectionException
     */
    public function validator(AbstractType $type, bool $allowExtraFields = false, array $params = [])
    {
        if (!$params) {
            $params = json_decode($this->request->getContent(), true);
        }

        if (!is_array($params)) {
            $params = [];
        }

        if (empty($params['form']) || !is_array($params['form'])) {
            $params['form'] = [];
        }

        $reflection = new ReflectionObject($type);

        $randomFields = uniqid('f_');

        $values = [];

        foreach ($params['form'] as $name => $value) {
            if ($reflection->hasProperty($name)) {
                $reflectionProperty = $reflection->getProperty($name);
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($type, $value);
            } elseif (!$allowExtraFields) {
                $values[$name] = '';
            }
        }

        $validator = Validation::createValidator();

        if ($values) {
            $values[$randomFields] = $randomFields;

            $constraints = new Collection([
                $randomFields => [
                    new NotBlank(),
                ],
            ]);

            $errors = $validator->validate($values, $constraints);
        } else {
            $errors = $this->validator->validate($type);
        }

        if ($errors->count()) {
            throw new ValidationParamsErrorException($errors);
        }
    }
    public function getControllerRepository(): ?AbstractRepository
    {
        return null;
    }

    /**
     * @param string $id
     *
     * @return mixed|null
     *
     * @throws InvalidComparisonException
     * @throws InvalidCriteriaException
     * @throws NonUniqueResultException
     */
    public function getAccessibleEntity(string $id)
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        $accessibleCriteria = static::getAccessibleCriteria();

        $accessibleCriteria[] = [
            'field' => 'id',
            'op' => 'eq',
            'value' => $id,
        ];

        return static::getControllerRepository()->getOneByCriteria($accessibleCriteria);
    }

    /**
     * @param array $criteria
     * @param array $order
     * @param int $skip
     * @param int $limit
     *
     * @return QueryResult
     *
     * @throws InvalidComparisonException
     * @throws InvalidCriteriaException
     * @throws OrderDirectionException
     */
    public function getAccessibleList(array $criteria, array $order = [], int $skip = 0, int $limit = 25): QueryResult
    {
        $accessibleCriteria = static::getAccessibleCriteria();

        foreach ($criteria as $key => $value) {
            if (is_numeric($key)) {
                $accessibleCriteria[] = $value;
            } else {
                $accessibleCriteria[$key] = $value;
            }
        }

        return static::getControllerRepository()->getResultByCriteria($accessibleCriteria, $order, $skip, $limit);
    }

    public function getAccessibleCriteria(): array
    {
        return [];
    }
}
