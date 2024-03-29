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

use Rapsys\AirBundle\Entity\Country;
use Rapsys\AirBundle\Entity\Dance;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Transformer\DanceTransformer;
use Rapsys\AirBundle\Transformer\SubscriptionTransformer;

use Rapsys\PackBundle\Util\ImageUtil;
use Rapsys\PackBundle\Util\SluggerUtil;

use Rapsys\UserBundle\Form\RegisterType as BaseRegisterType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * {@inheritdoc}
 */
class RegisterType extends BaseRegisterType {
	/**
	 * Constructor
	 *
	 * @param EntityManagerInterface $manager The entity manager
	 * @param ?ImageUtil $image The image instance
	 * @param ?SluggerUtil $slugger The slugger instance
	 * @param ?TranslatorInterface $translator The translator instance
	 */
	public function __construct(protected EntityManagerInterface $manager, protected ?ImageUtil $image = null, protected ?SluggerUtil $slugger = null, protected ?TranslatorInterface $translator = null) {
		//Call parent constructor
		parent::__construct($this->image, $this->slugger, $this->translator);
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void {
		//Add extra city field
		if (!empty($options['city'])) {
			$builder->add('city', TextType::class, ['attr' => ['placeholder' => 'Your city'], 'required' => false]);
		}

		//Add extra country field
		if (!empty($options['country'])) {
			//Add country field
			$builder->add('country', EntityType::class, ['class' => $options['country_class'], 'choice_label' => 'title'/*, 'choices' => $options['location_choices']*/, 'preferred_choices' => $options['country_favorites'], 'attr' => ['placeholder' => 'Your country'], 'choice_translation_domain' => false, 'required' => true, 'data' => $options['country_default']]);
		}

		//Add extra dance field
		if (!empty($options['dance'])) {
			//Add dance field
			#$builder->add('dances', EntityType::class, ['class' => $options['dance_class'], 'choice_label' => null, 'preferred_choices' => $options['dance_favorites'], 'attr' => ['placeholder' => 'Your dance'], 'choice_translation_domain' => false, 'required' => false, 'data' => $options['dance_default']]);
			$builder->add(
				$builder
					->create('dances', ChoiceType::class, ['attr' => ['placeholder' => 'Your dance']/*, 'by_reference' => false*/, 'choice_attr' => ['class' => 'row'], 'choice_translation_domain' => false, 'choices' => $options['dance_choices'], 'multiple' => true, 'preferred_choices' => $options['dance_favorites'], 'required' => false])
					->addModelTransformer(new DanceTransformer($this->manager))
					#->addModelTransformer(new CollectionToArrayTransformer)
			);
			/*, 'expanded' => true*/ /*, 'data' => $options['dance_default']*/
		}

		//Add extra phone field
		if (!empty($options['phone'])) {
			$builder->add('phone', TelType::class, ['attr' => ['placeholder' => 'Your phone'], 'required' => false]);
		}

		//Add extra pseudonym field
		if (!empty($options['pseudonym'])) {
			$builder->add('pseudonym', TextType::class, ['attr' => ['placeholder' => 'Your pseudonym'], 'required' => false]);
		}

		//Add extra subscription field
		if (!empty($options['subscription'])) {
			//Add subscription field
			#$builder->add('subscriptions', EntityType::class, ['class' => $options['subscription_class'], 'choice_label' => 'pseudonym', 'preferred_choices' => $options['subscription_favorites'], 'attr' => ['placeholder' => 'Your subscription'], 'choice_translation_domain' => false, 'required' => false, 'data' => $options['subscription_default']]);
			#$builder->add('subscriptions', ChoiceType::class, ['attr' => ['placeholder' => 'Your subscription'], 'choice_attr' => ['class' => 'row'], 'choice_translation_domain' => false, 'choices' => $options['subscription_choices'], 'multiple' => true, 'preferred_choices' => $options['subscription_favorites'], 'required' => false]);
			$builder->add(
				$builder
					//XXX: by_reference need to be false to allow persisting of data from the read only inverse side
					->create('subscriptions', ChoiceType::class, ['attr' => ['placeholder' => 'Your subscription']/*, 'by_reference' => false*/, 'choice_attr' => ['class' => 'row'], 'choice_translation_domain' => false, 'choices' => $options['subscription_choices'], 'multiple' => true, 'preferred_choices' => $options['subscription_favorites'], 'required' => false])
					->addModelTransformer(new SubscriptionTransformer($this->manager))
					#->addModelTransformer(new CollectionToArrayTransformer)
			);
			/*, 'expanded' => true*/ /*, 'data' => $options['subscription_default']*/
		}

		//Add extra zipcode field
		if (!empty($options['zipcode'])) {
			$builder->add('zipcode', TextType::class, ['attr' => ['placeholder' => 'Your zipcode'], 'required' => false]);
		}

		//Call parent
		parent::buildForm($builder, $options);
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver): void {
		//Call parent configure options
		parent::configureOptions($resolver);

		//Set defaults
		$resolver->setDefaults(
			[
				'city' => true,
				'country' => true,
				'country_class' => 'Rapsys\AirBundle\Entity\Country',
				'country_default' => null,
				'country_favorites' => [],
				'dance' => false,
				'dance_choices' => [],
				#'dance_default' => null,
				'dance_favorites' => [],
				'phone' => true,
				'pseudonym' => true,
				'subscription' => false,
				'subscription_choices' => [],
				#'subscription_default' => null,
				'subscription_favorites' => [],
				'zipcode' => true
			]
		);

		//Add extra city option
		$resolver->setAllowedTypes('city', 'boolean');

		//Add extra country option
		$resolver->setAllowedTypes('country', 'boolean');

		//Add country class
		$resolver->setAllowedTypes('country_class', 'string');

		//Add country default
		$resolver->setAllowedTypes('country_default', [Country::class, 'null']);

		//Add country favorites
		$resolver->setAllowedTypes('country_favorites', 'array');

		//Add extra dance option
		$resolver->setAllowedTypes('dance', 'boolean');

		//Add dance choices
		$resolver->setAllowedTypes('dance_choices', 'array');

		//Add dance default
		#$resolver->setAllowedTypes('dance_default', 'integer');

		//Add dance favorites
		$resolver->setAllowedTypes('dance_favorites', 'array');

		//Add extra phone option
		$resolver->setAllowedTypes('phone', 'boolean');

		//Add extra pseudonym option
		$resolver->setAllowedTypes('pseudonym', 'boolean');

		//Add extra subscription option
		$resolver->setAllowedTypes('subscription', 'boolean');

		//Add subscription choices
		$resolver->setAllowedTypes('subscription_choices', 'array');

		//Add subscription default
		#$resolver->setAllowedTypes('subscription_default', 'integer');

		//Add subscription favorites
		$resolver->setAllowedTypes('subscription_favorites', 'array');

		//Add extra zipcode option
		$resolver->setAllowedTypes('zipcode', 'boolean');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return 'rapsysair_register';
	}
}
