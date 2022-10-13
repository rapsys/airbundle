<?php

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

use Rapsys\AirBundle\Form\Extension\Type\HiddenEntityType;
use Rapsys\AirBundle\Entity\Snippet;

class SnippetType extends AbstractType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $form, array $options): FormBuilderInterface {
		//Start build form
		$form
			//Add locale, location and user hidden fields
			->add('locale', HiddenType::class, ['required' => true])
			->add('location', HiddenEntityType::class, ['required' => true])
			->add('user', HiddenEntityType::class, ['required' => true]);

		//With description
		if ($options['description']) {
			$form->add('description', TextareaType::class, ['attr' => ['placeholder' => 'Your description', 'cols' => 50, 'rows' => 15], 'required' => false]);
		}

		//With class
		if ($options['class']) {
			$form->add('class', TextareaType::class, ['attr' => ['placeholder' => 'Your class', 'cols' => 50, 'rows' => 10], 'required' => false]);
		}

		//With short
		if ($options['short']) {
			$form->add('short', TextareaType::class, ['attr' => ['placeholder' => 'Your short', 'cols' => 50, 'rows' => 10], 'required' => false]);
		}

		//With rate
		if ($options['rate']) {
			$form->add('rate', NumberType::class, ['attr' => ['placeholder' => 'Your rate'], 'required' => false]);
		}

		//With hat
		if ($options['hat']) {
			$form->add('hat', CheckboxType::class, ['attr' => ['placeholder' => 'Your hat'], 'required' => false]);
		}

		//With contact
		if ($options['contact']) {
			$form->add('contact', UrlType::class, ['attr' => ['placeholder' => 'Your contact'], 'required' => false]);
		}

		//With donate
		if ($options['donate']) {
			$form->add('donate', UrlType::class, ['attr' => ['placeholder' => 'Your donate'], 'required' => false]);
		}

		//With link
		if ($options['link']) {
			$form->add('link', UrlType::class, ['attr' => ['placeholder' => 'Your link'], 'required' => false]);
		}

		//With profile
		if ($options['profile']) {
			$form->add('profile', UrlType::class, ['attr' => ['placeholder' => 'Your profile'], 'required' => false]);
		}

		//Add submit
		$form->add('submit', SubmitType::class, ['label' => 'Send', 'attr' => ['class' => 'submit']]);

		//Return form builder
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver): void {
		//Set defaults
		$resolver->setDefaults(['class' => true, 'contact' => true, 'data_class' => Snippet::class, 'description' => true, 'donate' => true, 'error_bubbling' => true, 'hat' => true, 'link' => true, 'profile' => true, 'rate' => true, 'short' => true]);

		//Add extra class option
		$resolver->setAllowedTypes('class', 'boolean');

		//Add extra contact option
		$resolver->setAllowedTypes('contact', 'boolean');

		//Add extra description option
		$resolver->setAllowedTypes('description', 'boolean');

		//Add extra donate option
		$resolver->setAllowedTypes('donate', 'boolean');

		//Add extra hat option
		$resolver->setAllowedTypes('hat', 'boolean');

		//Add extra link option
		$resolver->setAllowedTypes('link', 'boolean');

		//Add extra profile option
		$resolver->setAllowedTypes('profile', 'boolean');

		//Add extra rate option
		$resolver->setAllowedTypes('rate', 'boolean');

		//Add extra short option
		$resolver->setAllowedTypes('short', 'boolean');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return 'rapsys_air_snippet';
	}
}
