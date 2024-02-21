<?php

namespace Rapsys\AirBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

use Rapsys\AirBundle\Entity\Session;

class WeatherCommand extends DoctrineCommand {
	//Set failure constant
	const FAILURE = 1;

	///Set success constant
	const SUCCESS = 0;

	///Set Tidy config
	private $config = [
		//Mostly useless in fact
		'indent' => true,
		//Required to simplify simplexml transition
		'output-xml' => true,
		//Required to avoid xml errors
		'quote-nbsp' => false,
		//Required to fix code
		'clean' => true
	];

	///Set accuweather uris
	private $accuweather = [
		//Hourly uri
		'hourly' => [
			75001 => 'https://www.accuweather.com/en/fr/paris-01-louvre/75001/hourly-weather-forecast/179142_pc?day=',
			75004 => 'https://www.accuweather.com/en/fr/paris-04-hotel-de-ville/75004/hourly-weather-forecast/179145_pc?day=',
			75005 => 'https://www.accuweather.com/en/fr/paris-05-pantheon/75005/hourly-weather-forecast/179146_pc?day=',
			75006 => 'https://www.accuweather.com/fr/fr/paris-06-luxembourg/75006/hourly-weather-forecast/179147_pc?day=',
			75007 => 'https://www.accuweather.com/en/fr/paris-07-palais-bourbon/75007/hourly-weather-forecast/179148_pc?day=',
			75009 => 'https://www.accuweather.com/en/fr/paris-09-opera/75009/hourly-weather-forecast/179150_pc?day=',
			75010 => 'https://www.accuweather.com/en/fr/paris-10-entrepot/75010/hourly-weather-forecast/179151_pc?day=',
			75012 => 'https://www.accuweather.com/en/fr/paris-12-reuilly/75012/hourly-weather-forecast/179153_pc?day=',
			75013 => 'https://www.accuweather.com/en/fr/paris-13-gobelins/75013/hourly-weather-forecast/179154_pc?day=',
			75015 => 'https://www.accuweather.com/en/fr/paris-15-vaugirard/75015/hourly-weather-forecast/179156_pc?day=',
			75019 => 'https://www.accuweather.com/en/fr/paris-19-buttes-chaumont/75019/hourly-weather-forecast/179160_pc?day=',
			75116 => 'https://www.accuweather.com/en/fr/paris-16-passy/75116/hourly-weather-forecast/179246_pc?day='
		],
		//Daily uri
		'daily' => [
			75001 => 'https://www.accuweather.com/en/fr/paris-01-louvre/75001/daily-weather-forecast/179142_pc',
			75004 => 'https://www.accuweather.com/en/fr/paris-04-hotel-de-ville/75004/daily-weather-forecast/179145_pc',
			75005 => 'https://www.accuweather.com/en/fr/paris-05-pantheon/75005/daily-weather-forecast/179146_pc',
			75006 => 'https://www.accuweather.com/fr/fr/paris-06-luxembourg/75006/daily-weather-forecast/179147_pc',
			75007 => 'https://www.accuweather.com/en/fr/paris-07-palais-bourbon/75007/daily-weather-forecast/179148_pc',
			75009 => 'https://www.accuweather.com/en/fr/paris-09-opera/75009/daily-weather-forecast/179150_pc',
			75010 => 'https://www.accuweather.com/en/fr/paris-10-entrepot/75010/daily-weather-forecast/179151_pc',
			75012 => 'https://www.accuweather.com/en/fr/paris-12-reuilly/75012/daily-weather-forecast/179153_pc',
			75013 => 'https://www.accuweather.com/en/fr/paris-13-gobelins/75013/daily-weather-forecast/179154_pc',
			75015 => 'https://www.accuweather.com/en/fr/paris-15-vaugirard/75015/daily-weather-forecast/179156_pc',
			75019 => 'https://www.accuweather.com/en/fr/paris-19-buttes-chaumont/75019/daily-weather-forecast/179160_pc',
			75116 => 'https://www.accuweather.com/en/fr/paris-16-passy/75116/daily-weather-forecast/179246_pc'
		]
	];

	///Set curl handler
	private $ch = null;

	///Set manager registry
	private $doctrine;

	///Set filesystem
	private $filesystem;

	///Weather command constructor
	public function __construct(ManagerRegistry $doctrine, Filesystem $filesystem) {
		parent::__construct($doctrine);

		//Set entity manager
		$this->doctrine = $doctrine;

		//Set filesystem
		$this->filesystem = $filesystem;
	}

	///Configure attribute command
	protected function configure() {
		//Configure the class
		$this
			//Set name
			->setName('rapsysair:weather')
			//Set description shown with bin/console list
			->setDescription('Updates session rain and temperature fields')
			//Set description shown with bin/console --help airlibre:attribute
			->setHelp('This command updates session rain and temperature fields in next three days')
			//Add daily and hourly aliases
			->setAliases(['rapsysair:weather:daily', 'rapsysair:weather:hourly']);
	}

	///Process the attribution
	protected function execute(InputInterface $input, OutputInterface $output) {
		//Kernel object
		$kernel = $this->getApplication()->getKernel();

		//Tmp directory
		$tmpdir = $kernel->getContainer()->getParameter('kernel.project_dir').'/var/cache/weather';

		//Set tmpdir
		//XXX: worst case scenario we have 3 files per zipcode plus daily
		if (!is_dir($tmpdir)) {
			try {
				//Create dir
				$this->filesystem->mkdir($tmpdir, 0775);
			} catch (IOException $exception) {
				//Display error
				echo 'Create dir '.$exception->getPath().' failed'."\n";

				//Exit with failure
				exit(self::FAILURE);
			}
		}

		//Cleanup kernel
		unset($kernel);

		//Tidy object
		$tidy = new \tidy();

		//Init zipcodes array
		$zipcodes = [];

		//Init types
		$types = [];

		//Process hourly accuweather
		if (($command = $input->getFirstArgument()) == 'rapsysair:weather:hourly' || $command == 'rapsysair:weather') {
			//Fetch hourly sessions to attribute
			$types['hourly'] = $this->doctrine->getRepository(Session::class)->findAllPendingHourlyWeather();

			//Iterate on each session
			foreach($types['hourly'] as $sessionId => $session) {
				//Get zipcode
				$zipcode = $session->getLocation()->getZipcode();

				//Get start
				$start = $session->getStart();

				//Set start day
				$day = $start->diff((new \DateTime('now'))->setTime(0, 0, 0))->d + 1;

				//Check if zipcode is set
				if (!isset($zipcodes[$zipcode])) {
					$zipcodes[$zipcode] = [];
				}

				//Check if zipcode date is set
				if (!isset($zipcodes[$zipcode][$day])) {
					$zipcodes[$zipcode][$day] = [ $sessionId => $sessionId ];
				} else {
					$zipcodes[$zipcode][$day][$sessionId] = $sessionId;
				}

				//Get stop
				$stop = $session->getStop();

				//Set stop day
				$day = $stop->diff((new \DateTime('now'))->setTime(0, 0, 0))->d + 1;

				//Check if zipcode date is set
				if (!isset($zipcodes[$zipcode][$day])) {
					$zipcodes[$zipcode][$day] = [ $sessionId => $sessionId ];
				} else {
					$zipcodes[$zipcode][$day][$sessionId] = $sessionId;
				}
			}
		}

		//Process daily accuweather
		if ($command == 'rapsysair:weather:daily' || $command == 'rapsysair:weather') {
			//Fetch daily sessions to attribute
			$types['daily'] = $this->doctrine->getRepository(Session::class)->findAllPendingDailyWeather();

			//Iterate on each session
			foreach($types['daily'] as $sessionId => $session) {
				//Get zipcode
				$zipcode = $session->getLocation()->getZipcode();

				//Get start
				$start = $session->getStart();

				//Set start day
				$day = 'daily';

				//Check if zipcode is set
				if (!isset($zipcodes[$zipcode])) {
					$zipcodes[$zipcode] = [];
				}

				//Check if zipcode date is set
				if (!isset($zipcodes[$zipcode][$day])) {
					$zipcodes[$zipcode][$day] = [ $sessionId => $sessionId ];
				} else {
					$zipcodes[$zipcode][$day][$sessionId] = $sessionId;
				}
			}
		}

		//Init curl
		$this->curl_init();

		//Init data array
		$data = [];

		//Iterate on zipcodes
		foreach($zipcodes as $zipcode => $days) {
			//Iterate on days
			foreach($days as $day => $null) {
				//Try to load content from cache
				if (!is_file($file = $tmpdir.'/'.$zipcode.'.'.$day.'.html') || stat($file)['ctime'] <= (time() - ($day == 'daily' ? 4 : 2)*3600) || ($content = file_get_contents($file)) === false) {
					//Prevent timing detection
					//XXX: from 0.1 to 5 seconds
					usleep(rand(1,50) * 100000); 

					//Get content
					//TODO: for daily we may load data for requested quarter of the day
					$content = $this->curl_get($day == 'daily' ? $this->accuweather['daily'][$zipcode] : $this->accuweather['hourly'][$zipcode].$day);

					//Store it
					if (file_put_contents($tmpdir.'/'.$zipcode.'.'.$day.'.html', $content) === false) {
						//Display error
						echo 'Write to '.$tmpdir.'/'.$zipcode.'.'.$day.'.html failed'."\n";

						//Exit with failure
						exit(self::FAILURE);
					}
				}

				//Parse string
				$tidy->parseString($content, $this->config, 'utf8');

				//Fix error buffer
				//XXX: don't care about theses errors, tidy is here to fix...
				#if (!empty($tidy->errorBuffer)) {
				#	var_dump($tidy->errorBuffer);
				#	die('Tidy errors');
				#}

				//Load simplexml
				//XXX: trash all xmlns= broken tags
				$sx = new \SimpleXMLElement(str_replace(['xmlns=', 'xlink:href='], ['xns=', 'href='], $tidy));

				//Process daily
				if ($day == 'daily') {
					//Iterate on each link containing data
					foreach($sx->xpath('//a[contains(@class,"daily-forecast-card")]') as $node) {
						//Get date
						$dsm = trim($node->div[0]->h2[0]->span[1]);

						//Get temperature
						$temperature = str_replace('°', '', $node->div[0]->div[0]->span[0]);

						//Get rainrisk
						$rainrisk = trim(str_replace('%', '', $node->div[1]))/100;

						//Store data
						$data[$zipcode][$dsm]['daily'] = [
							'temperature' => $temperature,
							'rainrisk' => $rainrisk
						];
					}
				//Process hourly
				} else {
					//Iterate on each div containing data
					#(string)$sx->xpath('//div[@class="hourly-card-nfl"]')[0]->attributes()->value
					#/html/body/div[1]/div[5]/div[1]/div[1]/div[1]/div[1]/div[1]/div/h2/span[1]
					foreach($sx->xpath('//div[@data-shared="false"]') as $node) {
						//Get hour
						$hour = trim(str_replace(' h', '', $node->div[0]->div[0]->div[0]->div[0]->div[0]->h2[0]));

						//Compute dsm from day (1=d,2=d+1,3=d+2)
						$dsm = (new \DateTime('+'.($day - 1).' day'))->format('d/m');

						//Get temperature
						$temperature = str_replace('°', '', $node->div[0]->div[0]->div[0]->div[0]->div[1]);

						//Get realfeel
						$realfeel = trim(str_replace(['RealFeel®', '°'], '', $node->div[0]->div[0]->div[0]->div[1]->div[0]->div[0]->div[0]));

						//Get rainrisk
						$rainrisk = floatval(str_replace('%', '', trim($node->div[0]->div[0]->div[0]->div[2]->div[0]))/100);

						//Set rainfall to 0 (mm)
						$rainfall = 0;

						//Iterate on each entry
						//TODO: wind and other infos are present in $node->div[1]->div[0]->div[1]->div[0]->p
						foreach($node->div[1]->div[0]->div[1]->div[0]->p as $p) {
							//Lookup for rain entry if present
							if (in_array(trim($p), ['Rain', 'Pluie'])) {
								//Get rainfall
								$rainfall = floatval(str_replace(' mm', '', $p->span[0]));
							}
						}

						//Store data
						$data[$zipcode][$dsm][$hour] = [
							'temperature' => $temperature,
							'realfeel' => $realfeel,
							'rainrisk' => $rainrisk,
							'rainfall' => $rainfall
						];
					}
				}

				//Cleanup
				unset($sx);
			}
		}

		//Iterate on types
		foreach($types as $type => $sessions) {
			//Iterate on each type
			foreach($sessions as $sessionId => $session) {
				//Get zipcode
				$zipcode = $session->getLocation()->getZipcode();

				//Get start
				$start = $session->getStart();

				//Daily type
				if ($type == 'daily') {
					//Set period
					$period = [ $start ];
				//Hourly type
				} else {
					//Get stop
					$stop = $session->getStop();

					//Compute period
					$period = new \DatePeriod(
						//Start from begin
						$start,
						//Iterate on each hour
						new \DateInterval('PT1H'),
						//End with begin + length
						$stop
					);
				}

				//Set meteo
				$meteo = [
					'rainfall' => null,
					'rainrisk' => null,
					'realfeel' => [],
					'realfeelmin' => null,
					'realfeelmax' => null,
					'temperature' => [],
					'temperaturemin' => null,
					'temperaturemax' => null
				];

				//Iterate on the period
				foreach($period as $time) {
					//Set dsm
					$dsm = $time->format('d/m');

					//Set hour
					$hour = $type=='daily'?$type:$time->format('H');

					//Check data availability
					//XXX: sometimes startup delay causes weather data to be unavailable for session first hour
					if (!isset($data[$zipcode][$dsm][$hour])) {
						//Skip unavailable data
						continue;
					}

					//Set info alias
					$info = $data[$zipcode][$dsm][$hour];

					//Check if rainrisk is higher
					if ($meteo['rainrisk'] === null || $info['rainrisk'] > $meteo['rainrisk']) {
						//Set highest rain risk
						$meteo['rainrisk'] = floatval($info['rainrisk']);
					}

					//Check if rainfall is set
					if (isset($info['rainfall'])) {
						//Set rainfall sum
						$meteo['rainfall'] += floatval($info['rainfall']);
					}

					//Add temperature
					$meteo['temperature'][$hour] = $info['temperature'];

					//Hourly type
					if ($type != 'daily') {
						//Check min temperature
						if ($meteo['temperaturemin'] === null || $info['temperature'] < $meteo['temperaturemin']) {
							$meteo['temperaturemin'] = floatval($info['temperature']);
						}

						//Check max temperature
						if ($meteo['temperaturemax'] === null || $info['temperature'] > $meteo['temperaturemax']) {
							$meteo['temperaturemax'] = floatval($info['temperature']);
						}
					}

					//Check if realfeel is set
					if (isset($info['realfeel'])) {
						//Add realfeel
						$meteo['realfeel'][$hour] = $info['realfeel'];

						//Check min realfeel
						if ($meteo['realfeelmin'] === null || $info['realfeel'] < $meteo['realfeelmin']) {
							$meteo['realfeelmin'] = floatval($info['realfeel']);
						}

						//Check max realfeel
						if ($meteo['realfeelmax'] === null || $info['realfeel'] > $meteo['realfeelmax']) {
							$meteo['realfeelmax'] = floatval($info['realfeel']);
						}
					}
				}

				//Check if rainfall is set and differ
				if ($session->getRainfall() !== $meteo['rainfall']) {
					//Set rainfall
					$session->setRainfall($meteo['rainfall']);
				}

				//Check if rainrisk differ
				if ($session->getRainrisk() !== $meteo['rainrisk']) {
					//Set rainrisk
					$session->setRainrisk($meteo['rainrisk']);
				}

				//Check realfeel array
				if ($meteo['realfeel'] !== []) {
					//Compute realfeel
					$realfeel = floatval(round(array_sum($meteo['realfeel'])/count($meteo['realfeel']),1));

					//Check if realfeel differ
					if ($session->getRealfeel() !== $realfeel) {
						//Set average realfeel
						$session->setRealfeel($realfeel);
					}

					//Check if realfeelmin differ
					if ($session->getRealfeelmin() !== $meteo['realfeelmin']) {
						//Set realfeelmin
						$session->setRealfeelmin($meteo['realfeelmin']);
					}

					//Check if realfeelmax differ
					if ($session->getRealfeelmax() !== $meteo['realfeelmax']) {
						//Set realfeelmax
						$session->setRealfeelmax($meteo['realfeelmax']);
					}
				}

				//Check temperature array
				if ($meteo['temperature'] !== []) {
					//Compute temperature
					$temperature = floatval(round(array_sum($meteo['temperature'])/count($meteo['temperature']),1));

					//Check if temperature differ
					if ($session->getTemperature() !== $temperature) {
						//Set average temperature
						$session->setTemperature($temperature);
					}

					//Check if temperaturemin differ
					if ($session->getTemperaturemin() !== $meteo['temperaturemin']) {
						//Set temperaturemin
						$session->setTemperaturemin($meteo['temperaturemin']);
					}

					//Check if temperaturemax differ
					if ($session->getTemperaturemax() !== $meteo['temperaturemax']) {
						//Set temperaturemax
						$session->setTemperaturemax($meteo['temperaturemax']);
					}
				}
			}
		}

		//Flush to get the ids
		$this->doctrine->getManager()->flush();

		//Close curl handler
		$this->curl_close();

		//Return success
		return self::SUCCESS;
	}

	/**
	 * Init curl handler
	 *
	 * @return bool|void Return success or exit
	 */
	function curl_init() {
		//Init curl
		if (($this->ch = curl_init()) === false) {
			//Display error
			echo 'Curl init failed: '.curl_error($this->ch)."\n";
			//Exit with failure
			exit(self::FAILURE);
		}

		//Set curl options
		if (
			curl_setopt_array(
				$this->ch,
				[
					//Force http2
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
					//Set http headers
					CURLOPT_HTTPHEADER => [
						//XXX: it seems that you can disable akamai fucking protection with Pragma: akamai-x-cache-off
						//XXX: see https://support.globaldots.com/hc/en-us/articles/115003996705-Akamai-Pragma-Headers-overview
						#'Pragma: akamai-x-cache-off',
						//XXX: working curl command
						#curl --http2 --cookie file.jar --cookie-jar file.jar -v -i -k -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9' -H 'Accept-Language: en-GB,en;q=0.9' -H 'Cache-Control: no-cache' -H 'Connection: keep-alive' -H 'Host: www.accuweather.com' -H 'Pragma: no-cache' -H 'Upgrade-Insecure-Requests: 1' -H 'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36' 'https://www.accuweather.com/'
						//Set accept
						'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
						//Set accept language
						'Accept-Language: en-GB,en;q=0.9',
						//Disable cache
						'Cache-Control: no-cache',
						//Keep connection alive
						'Connection: keep-alive',
						//Disable cache
						'Pragma: no-cache',
						//Force secure requests
						'Upgrade-Insecure-Requests: 1',
						//Set user agent
						'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36',
						//Force akamai cookie
						//XXX: seems to come from http request
						'Cookie: AKA_A2=A',
					],
					//Enable cookie
					CURLOPT_COOKIEFILE => '',
					//Disable location following
					CURLOPT_FOLLOWLOCATION => false,
					//Set url
					#CURLOPT_URL => $url = 'https://www.accuweather.com/',
					//Return headers too
					CURLOPT_HEADER => true,
					//Return content
					CURLOPT_RETURNTRANSFER => true,

					//XXX: debug
					CURLINFO_HEADER_OUT => true
				]
			) === false
		) {
			//Display error
			echo 'Curl setopt array failed: '.curl_error($this->ch)."\n";
			//Exit with failure
			exit(self::FAILURE);
		}

		//Return success
		return true;
	}

	/**
	 * Get url
	 *
	 * @return string|void Return url content or exit
	 */
	function curl_get($url) {
		//Set url to fetch
		if (curl_setopt($this->ch, CURLOPT_URL, $url) === false) {
			//Display error
			echo 'Setopt for '.$url.' failed: '.curl_error($this->ch)."\n";

			//Close curl handler
			curl_close($this->ch);

			//Exit with failure
			exit(self::FAILURE);
		}

		//Check return status
		if (($response = curl_exec($this->ch)) === false) {
			//Display error
			echo 'Get for '.$url.' failed: '.curl_error($this->ch)."\n";

			//Display sent headers
			var_dump(curl_getinfo($this->ch, CURLINFO_HEADER_OUT));

			//Display response
			var_dump($response);

			//Close curl handler
			curl_close($this->ch);

			//Exit with failure
			exit(self::FAILURE);
		}

		//Get header size
		if (empty($hs = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE))) {
			//Display error
			echo 'Getinfo for '.$url.' failed: '.curl_error($this->ch)."\n";

			//Display sent headers
			var_dump(curl_getinfo($this->ch, CURLINFO_HEADER_OUT));

			//Display response
			var_dump($response);

			//Close curl handler
			curl_close($this->ch);

			//Exit with failure
			exit(self::FAILURE);
		}

		//Get header
		if (empty($header = substr($response, 0, $hs))) {
			//Display error
			echo 'Header for '.$url.' empty: '.curl_error($this->ch)."\n";

			//Display sent headers
			var_dump(curl_getinfo($this->ch, CURLINFO_HEADER_OUT));

			//Display response
			var_dump($response);

			//Close curl handler
			curl_close($this->ch);

			//Exit with failure
			exit(self::FAILURE);
		}

		//Check request success
		if (strlen($header) <= 10 || substr($header, 0, 10) !== 'HTTP/2 200') {
			//Display error
			echo 'Status for '.$url.' failed: '.curl_error($this->ch)."\n";

			//Display sent headers
			var_dump(curl_getinfo($this->ch, CURLINFO_HEADER_OUT));

			//Display response
			var_dump($header);

			//Close curl handler
			curl_close($this->ch);

			//Exit with failure
			exit(self::FAILURE);
		}

		//Return content
		return substr($response, $hs);
	}

	/**
	 * Close curl handler
	 *
	 * @return bool Return success or failure
	 */
	function curl_close() {
		return curl_close($this->ch);
	}
}
