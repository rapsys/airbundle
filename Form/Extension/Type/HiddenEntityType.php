<?php

namespace Rapsys\AirBundle\Form\Extension\Type;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Hidden Entity Type class definition
 *
 * @see https://symfony.com/doc/current/form/create_custom_field_type.html
 */
class HiddenEntityType extends HiddenType implements DataTransformerInterface {
	/**
	 * @var ManagerRegistry $dm
	 */
	private $dm;

	/**
	 * @var string $class
	 */
	private $class;

	/**
	 * Constructor
	 *
	 * @param ManagerRegistry $doctrine
	 */
	public function __construct(ManagerRegistry $doctrine) {
		$this->dm = $doctrine;
	}

	/**
	 * Configure options
	 *
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver): void {
		//Call parent
		parent::configureOptions($resolver);

		//Derive "data_class" option from passed "data" object
		$class = function (Options $options) {
			return isset($options['data']) && \is_object($options['data']) ? \get_class($options['data']) : null;
		};

		$resolver->setDefaults([
			#'data_class' => null,
			'class' => $class
		]);
	}

	/**
	 * Build form
	 *
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void {
		//Set class from options['class']
		$this->class = $options['class'];

		//Without class
		if (empty($this->class)) {
			//Set class from namespace and field name
			$this->class = str_replace('Form\\Extension\\Type', 'Entity\\' ,__NAMESPACE__).ucfirst($builder->getName());
		//With bundle named entity
		} elseif (
			($pos = strpos($this->class, ':')) !== false &&
			!empty($entity = substr($this->class, $pos + 1))
		) {
			//Set class from namespace and entity name
			$this->class = str_replace('Form\\Extension\\Type', 'Entity\\' ,__NAMESPACE__).$entity;
		}

		//Set this as model transformer
		//XXX: clone the model transformer to allow multiple hiddenentitytype field with different class
		$builder->addModelTransformer(clone $this);
	}

	/**
	 * Transform data to string
	 *
	 * @param mixed $data The data object
	 * @return string The object id
	 */
	public function transform($data): string {
		//Modified from comments to use instanceof so that base classes or interfaces can be specified
		if ($data === null || !$data instanceof $this->class) {
			return '';
		}

		$res = $data->getId();

		return $res;
	}

	/**
	 * Reverse transformation from string to data object
	 *
	 * @param mixed $value The object id
	 * @return mixed The data object
	 */
	public function reverseTransform(mixed $value): mixed {
		if (!$value) {
			return null;
		}

		$res = null;
		try {
			$rep = $this->dm->getRepository($this->class);
			$res = $rep->findOneById($value);
		} catch (\Exception $e) {
			throw new TransformationFailedException($e->getMessage());
		}

		if ($res === null) {
			throw new TransformationFailedException(sprintf('A %s with id %s does not exist!', $this->class, $value));
		}

		return $res;
	}
}
