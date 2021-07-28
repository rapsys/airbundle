<?php

namespace Rapsys\AirBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Rapsys\AirBundle\Entity\Session;

class RekeyCommand extends DoctrineCommand {
	//Set failure constant
	const FAILURE = 1;

	///Set success constant
	const SUCCESS = 0;

	///Configure attribute command
	protected function configure() {
		//Configure the class
		$this
			//Set name
			->setName('rapsysair:rekey')
			//Set description shown with bin/console list
			->setDescription('Rekey sessions')
			//Set description shown with bin/console --help airlibre:attribute
			->setHelp('This command rekey sessions in chronological order');
	}

	///Process the attribution
	protected function execute(InputInterface $input, OutputInterface $output) {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Rekey sessions
		if (!$doctrine->getRepository(Session::class)->rekey()) {
			//Return failure
			return self::FAILURE;
		}

		//Return success
		return self::SUCCESS;
	}

	/**
	 * Return the bundle alias
	 *
	 * {@inheritdoc}
	 */
	public function getAlias(): string {
		return 'rapsys_air';
	}
}
