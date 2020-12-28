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
    public function configureOptions(OptionsResolver $resolver) {
		//Call parent
        parent::configureOptions($resolver);

        // Derive "data_class" option from passed "data" object
        $class = function (Options $options) {
            return isset($options['data']) && \is_object($options['data']) ? \get_class($options['data']) : null;
        };

        $resolver->setDefaults([
            'class' => $class
		]);
	}


	/**
	 * Build form
	 *
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void {
		#$this->entityClass = sprintf('App\Entity\%s', ucfirst($builder->getName()));
		#var_dump($builder->getName());
		#$this->entityClass = sprintf('App\Entity\%s', ucfirst($builder->getName()));
		//$this->dataClass[$builder->getName()] = $options['data_class'];

		//Set class from options['class']
		$this->class = $options['class'];

		//Check class
		if (empty($this->class)) {
			//Set class from namespace and field name
			$this->class = str_replace('Form\\Extension\\Type', 'Entity\\' ,__NAMESPACE__).ucfirst($builder->getName());
		}

		//Set this as model transformer
		//XXX: clone the model transformer to allow multiple hiddenentitytype field with different class
		$builder->addModelTransformer(clone $this);
	}

	public function transform($data): string {
		// Modified from comments to use instanceof so that base classes or interfaces can be specified
		if ($data === null || !$data instanceof $this->class) {
			return '';
		}

		$res = $data->getId();

		return $res;
	}

	public function reverseTransform($data) {
		if (!$data) {
			return null;
		}

		$res = null;
		try {
			$rep = $this->dm->getRepository($this->class);
			$res = $rep->findOneById($data);
		} catch (\Exception $e) {
			throw new TransformationFailedException($e->getMessage());
		}

		if ($res === null) {
			throw new TransformationFailedException(sprintf('A %s with id "%s" does not exist!', $this->class, $data));
		}

		return $res;
	}
}
