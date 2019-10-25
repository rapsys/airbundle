<?php

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		return $builder->add('name', TextType::class, array('attr' => array('placeholder' => 'Your name'), 'constraints' => array(new NotBlank(array('message' => 'Please provide your name')))))
			->add('subject', TextType::class, array('attr' => array('placeholder' => 'Subject'), 'constraints' => array(new NotBlank(array('message' => 'Please provide your subject')))))
			->add('mail', EmailType::class, array('attr' => array('placeholder' => 'Your mail address'), 'constraints' => array(new NotBlank(array('message' => 'Please provide a valid mail')), new Email(array('message' => 'Your mail doesn\'t seems to be valid')))))
			->add('message', TextareaType::class, array('attr' => array('placeholder' => 'Your message here', 'cols' => 50, 'rows' => 15), 'constraints' => array(new NotBlank(array('message' => 'Please provide your message')))))
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
		return 'contact_form';
	}
}
