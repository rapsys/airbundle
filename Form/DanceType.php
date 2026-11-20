<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Form;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use Rapsys\AirBundle\Transformer\DanceTransformer;

/**
 * {@inheritdoc}
 */
class DanceType extends AbstractType {
	/**
	 * Constructor
	 *
	 * @param EntityManagerInterface $manager The entity manager
	 * @param ?TranslatorInterface $translator The translator instance
	 */
	public function __construct(protected EntityManagerInterface $manager, protected ?TranslatorInterface $translator = null) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void {
		$builder->add(
			$builder
				->create('dances', ChoiceType::class, ['attr' => ['placeholder' => 'Your dance']/*, 'by_reference' => false*/, 'choice_attr' => ['class' => 'row'], 'choice_translation_domain' => false, 'choices' => $options['dance_choices'], 'multiple' => true, 'preferred_choices' => $options['dance_favorites'], 'required' => false])
				->addModelTransformer(new DanceTransformer($this->manager))
		);

		//Add submit
		$builder->add('submit', SubmitType::class, ['label' => 'Send', 'attr' => ['class' => 'submit']]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver): void {
		//Set defaults
		$resolver->setDefaults(['error_bubbling' => true, 'dance_choices' => [], 'dance_default' => null, 'dance_favorites' => []]);

		//Add dance choices
		$resolver->setAllowedTypes('dance_choices', 'array');

		//Add dance default
		$resolver->setAllowedTypes('dance_default', [Dance::class, 'null']);

		//Add dance favorites
		$resolver->setAllowedTypes('dance_favorites', 'array');

	}

	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return 'rapsysair_application';
	}
}
