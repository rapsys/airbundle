<?php

namespace Rapsys\AirBundle\Controller;

use Rapsys\AirBundle\Entity\Application;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Rapsys\UserBundle\Utils\Slugger;


class DefaultController extends AbstractController {
	//Config array
	protected $config;

	//Context array
	protected $context;

	//Router instance
	protected $router;

	//Slugger instance
	protected $slugger;

	//Translator instance
	protected $translator;

	/**
	 * Inject container and translator interface
	 *
	 * @param ContainerInterface $container The container instance
	 * @param RouterInterface $router The router instance
	 * @param Slugger $slugger The slugger instance
	 * @param TranslatorInterface $translator The translator instance
	 */
	public function __construct(ContainerInterface $container, RouterInterface $router, Slugger $slugger, TranslatorInterface $translator) {
		//Retrieve config
		$this->config = $container->getParameter($this->getAlias());

		//Set the router
		$this->router = $router;

		//Set the slugger
		$this->slugger = $slugger;

		//Set the translator
		$this->translator = $translator;

		//Set the context
		$this->context = [
			'copy_long' => $translator->trans($this->config['copy']['long']),
			'copy_short' => $translator->trans($this->config['copy']['short']),
			'site_ico' => $this->config['site']['ico'],
			'site_logo' => $this->config['site']['logo'],
			'site_png' => $this->config['site']['png'],
			'site_svg' => $this->config['site']['svg'],
			'site_title' => $translator->trans($this->config['site']['title']),
			'site_url' => $router->generate($this->config['site']['url'])
		];
	}

	/**
	 * The contact page
	 *
	 * @desc Send a contact mail to configured contact
	 *
	 * @param Request $request The request instance
	 * @param MailerInterface $mailer The mailer instance
	 *
	 * @return Response The rendered view or redirection
	 */
	public function contact(Request $request, MailerInterface $mailer) {
		//Set section
		$section = $this->translator->trans('Contact');

		//Set title
		$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

		//Create the form according to the FormType created previously.
		//And give the proper parameters
		$form = $this->createForm('Rapsys\AirBundle\Form\ContactType', null, [
			'action' => $this->generateUrl('rapsys_air_contact'),
			'method' => 'POST'
		]);

		if ($request->isMethod('POST')) {
			// Refill the fields in case the form is not valid.
			$form->handleRequest($request);

			if ($form->isValid()) {
				//Get data
				$data = $form->getData();

				//Create message
				$message = (new TemplatedEmail())
					//Set sender
					->from(new Address($data['mail'], $data['name']))
					//Set recipient
					//XXX: remove the debug set in vendor/symfony/mime/Address.php +46
					->to(new Address($this->config['contact']['mail'], $this->config['contact']['name']))
					//Set subject
					->subject($data['subject'])

					//Set path to twig templates
					->htmlTemplate('@RapsysAir/mail/contact.html.twig')
					->textTemplate('@RapsysAir/mail/contact.text.twig')

					//Set context
					->context(
						[
							'subject' => $data['subject'],
							'message' => strip_tags($data['message']),
						]+$this->context
					);

				//Try sending message
				//XXX: mail delivery may silently fail
				try {
					//Send message
					$mailer->send($message);

					//Redirect on the same route with sent=1 to cleanup form
					return $this->redirectToRoute($request->get('_route'), ['sent' => 1]+$request->get('_route_params'));
				//Catch obvious transport exception
				} catch(TransportExceptionInterface $e) {
					if ($message = $e->getMessage()) {
						//Add error message mail unreachable
						$form->get('mail')->addError(new FormError($this->translator->trans('Unable to contact: %mail%: %message%', ['%mail%' => $this->config['contact']['mail'], '%message%' => $this->translator->trans($message)])));
					} else {
						//Add error message mail unreachable
						$form->get('mail')->addError(new FormError($this->translator->trans('Unable to contact: %mail%', ['%mail%' => $this->config['contact']['mail']])));
					}
				}
			}
		}

		//Render template
		return $this->render('@RapsysAir/form/contact.html.twig', ['title' => $title, 'section' => $section, 'form' => $form->createView(), 'sent' => $request->query->get('sent', 0)]+$this->context);
	}

	/**
	 * The index page
	 *
	 * @desc Display all granted sessions with an application or login form
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view
	 */
	public function index(Request $request = null) {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Set section
		$section = $this->translator->trans('Index');

		//Set title
		$title = $section.' - '.$this->context['site_title'];

		//Init context
		$context = [];

		//Create application form for role_guest
		if ($this->isGranted('ROLE_GUEST')) {
			//Create ApplicationType form
			$application = $this->createForm('Rapsys\AirBundle\Form\ApplicationType', null, [
				//Set the action
				'action' => $this->generateUrl('rapsys_air_application_add'),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ],
				//Set admin
				'admin' => $this->isGranted('ROLE_ADMIN'),
				//Set default user to current
				'user' => $this->getUser()->getId(),
				//Set default slot to evening
				//XXX: default to Evening (3)
				'slot' => $doctrine->getRepository(Slot::class)->findOneById(3)
			]);

			//Add form to context
			$context['application'] = $application->createView();
		//Create login form for anonymous
		} elseif (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Create ApplicationType form
			$login = $this->createForm('Rapsys\UserBundle\Form\LoginType', null, [
				//Set the action
				'action' => $this->generateUrl('rapsys_user_login'),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ]
			]);

			//Add form to context
			$context['login'] = $login->createView();
		}

		//Compute period
		$period = new \DatePeriod(
			//Start from first monday of week
			new \DateTime('Monday this week'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next sunday and 4 weeks
			new \DateTime('Monday this week + 5 week')
		);

		//Fetch calendar
		$calendar = $doctrine->getRepository(Session::class)->fetchCalendarByDatePeriod($this->translator, $period, null, $request->get('session'), true);

		//Fetch locations
		$locations = $doctrine->getRepository(Location::class)->fetchTranslatedLocationByDatePeriod($this->translator, $period, true);

		//Render the view
		return $this->render('@RapsysAir/default/index.html.twig', ['title' => $title, 'section' => $section, 'calendar' => $calendar, 'locations' => $locations]+$context+$this->context);
	}


	/**
	 * The regulation page
	 *
	 * @desc Display the regulation policy
	 *
	 * @return Response The rendered view
	 */
	public function regulation() {
		//Set section
		$section = $this->translator->trans('Regulation');

		//Set title
		$title = $section.' - '.$this->context['site_title'];

		//Render template
		return $this->render('@RapsysAir/default/regulation.html.twig', ['title' => $title, 'section' => $section]+$this->context);
	}

	/**
	 * Return the bundle alias
	 *
	 * {@inheritdoc}
	 */
	public function getAlias() {
		return 'rapsys_air';
	}
}
