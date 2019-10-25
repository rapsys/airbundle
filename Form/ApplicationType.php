<?php

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\NotBlank;

class ApplicationType extends AbstractType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		return $builder
			->add('location', EntityType::class, array('class' => 'RapsysAirBundle:Location', 'choice_label' => 'title', 'attr' => array('placeholder' => 'Your location'), 'constraints' => array(new NotBlank(array('message' => 'Please provide your location')))))
			->add('date', DateType::class, array('attr' => [ 'placeholder' => 'Your date', 'class' => 'date' ], 'html5' => true, 'input' => 'datetime', 'data' => new \DateTime('+7 day'), 'constraints' => array(new NotBlank(array('message' => 'Please provide your date')), new Date(array('message' => 'Your date doesn\'t seems to be valid')))))
			->add('slot', EntityType::class, array('class' => 'RapsysAirBundle:Slot', 'choice_label' => 'title', 'attr' => array('placeholder' => 'Your slot'), 'constraints' => array(new NotBlank(array('message' => 'Please provide your slot')))))
			->add('submit', SubmitType::class, array('label' => 'Send', 'attr' => array('class' => 'submit')));
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(['error_bubbling' => true]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'rapsys_air_application';
	}
}
