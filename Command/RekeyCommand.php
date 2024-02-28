<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Rapsys\AirBundle\Command;
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
	protected function execute(InputInterface $input, OutputInterface $output): int {
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
