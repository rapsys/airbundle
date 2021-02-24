<?php

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Rapsys\AirBundle\Entity\Location;

class LocationType extends AbstractType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		return $builder
			->setAttribute('label_prefix', $options['label_prefix'])
			->add('title', TextType::class, ['attr' => ['placeholder' => 'Your title']])
			->add('short', TextType::class, ['attr' => ['placeholder' => 'Your short']])
			->add('address', TextType::class, ['attr' => ['placeholder' => 'Your address']])
			->add('zipcode', NumberType::class, ['attr' => ['placeholder' => 'Your zipcode'], 'html5' => true])
			->add('city', TextType::class, ['attr' => ['placeholder' => 'Your city']])
			->add('latitude', NumberType::class, ['attr' => ['placeholder' => 'Your latitude', 'step' => 0.000001], 'html5' => true, 'scale' => 6])
			->add('longitude', NumberType::class, ['attr' => ['placeholder' => 'Your longitude', 'step' => 0.000001], 'html5' => true, 'scale' => 6])
			->add('hotspot', CheckboxType::class, ['attr' => ['placeholder' => 'Your hotspot'], 'required' => false])
			->add('submit', SubmitType::class, ['label' => 'Send', 'attr' => ['class' => 'submit']]);
	}

	/**
	 * {@inheritdoc}
	 *
	public function buildView(FormView $view, FormInterface $form, array $options) {
		#$labelPrefix = $form->getRoot()->hasAttribute('label_prefix') ? $form->getRoot()->getAttribute('label_prefix') : '';
		#$labelPrefix = $form->getConfig()->hasAttribute('label_prefix');
		#$labelPrefix = $form->getConfig()->getAttribute('label_prefix');
		#var_dump($view);
		var_dump($view['label']);
		exit;
		//Prefix label to prevent collision
		$view['label'] = $form->getConfig()->getAttribute('label_prefix').$view->getVar('label');
	}*/

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(['data_class' => Location::class, 'error_bubbling' => true, 'label_prefix' => '']);
	}
}
