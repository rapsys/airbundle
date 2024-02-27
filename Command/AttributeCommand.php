<?php

namespace Rapsys\AirBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rapsys\AirBundle\Entity\Session;

class AttributeCommand extends DoctrineCommand {
	//Set failure constant
	const FAILURE = 1;

	///Set success constant
	const SUCCESS = 0;

	///Configure attribute command
	protected function configure() {
		//Configure the class
		$this
			//Set name
			->setName('rapsysair:attribute')
			//Set description shown with bin/console list
			->setDescription('Attribute sessions')
			//Set description shown with bin/console --help airlibre:attribute
			->setHelp('This command attribute sessions without application');
	}

	///Process the attribution
	protected function execute(InputInterface $input, OutputInterface $output): int {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Get manager
		$manager = $doctrine->getManager();

		//Fetch sessions to attribute
		$sessions = $doctrine->getRepository(Session::class)->findAllPendingApplication();

		//Iterate on each session
		foreach($sessions as $sessionId => $session) {
			//Extract session id
			if (!empty($sessionId)) {
				//Fetch application id of the best candidate
				if (!empty($application = $doctrine->getRepository(Session::class)->findBestApplicationById($sessionId))) {
					//Set updated
					$session->setUpdated(new \DateTime('now'));

					//Set application_id
					$session->setApplication($application);

					//Queue session save
					$manager->persist($session);

				}
			}
		}

		//Flush to get the ids
		$manager->flush();

		//Return success
		return self::SUCCESS;
	}
}
