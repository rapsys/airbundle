<?php

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Rapsys\AirBundle\Form\Extension\Type\HiddenEntityType;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Entity\Snippet;

class SnippetType extends AbstractType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		return $builder
			->add('locale', HiddenType::class, ['required' => true])
			->add('location', HiddenEntityType::class, ['required' => true])
			->add('user', HiddenEntityType::class, ['required' => true])
			->add('description', TextareaType::class, ['attr' => ['placeholder' => 'Your description', 'cols' => 50, 'rows' => 15], 'constraints' => [new NotBlank(['message' => 'Please provide your description'])], 'required' => true])
			->add('class', TextareaType::class, ['attr' => ['placeholder' => 'Your class', 'cols' => 50, 'rows' => 15], 'required' => false])
			->add('contact', UrlType::class, ['attr' => ['placeholder' => 'Your contact'], 'required' => false])
			->add('donate', UrlType::class, ['attr' => ['placeholder' => 'Your donate'], 'required' => false])
			->add('link', UrlType::class, ['attr' => ['placeholder' => 'Your link'], 'required' => false])
			->add('profile', UrlType::class, ['attr' => ['placeholder' => 'Your profile'], 'required' => false])
			->add('submit', SubmitType::class, ['label' => 'Send', 'attr' => ['class' => 'submit']]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(['data_class' => Snippet::class, 'error_bubbling' => true, 'location' => null, 'user' => null]);
		$resolver->setAllowedTypes('location', [Location::class, 'null']);
		$resolver->setAllowedTypes('user', [User::class, 'null']);
	}

	/**
	 * {@inheritdoc}
	 * XXX: this doesn't work, because it's impossible to generate this same id on other side
	 * TODO: we would need to be able to generate this id at form creation
	 *
	public function getBlockPrefix() {
		//Prevent collision between instances with an unique block prefix
		return 'snippet_'.uniqid();
	}*/
}
