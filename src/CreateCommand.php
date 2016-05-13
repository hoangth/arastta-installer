<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 16/12/2015
 * Time: 12:20
 */

namespace Arastta\Installer\Console;


use GuzzleHttp\Client;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use ZipArchive;

class CreateCommand extends Command {
	
	protected $input;

	public function configure() {

		$this->setName('create')
			->setDescription('Create a new Arastta instance into the project directory specified. Uses the standard compiled version.')
			->addArgument('name', InputArgument::OPTIONAL)
			->addOption('latest', null, InputOption::VALUE_NONE, 'Installs the latest from the master branch');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		
		$this->input = $input;

		$this->verifyApplicationDoesntExist();

		$output->writeln('<info>Creating Arastta Installation...</info>');

		$this->download($zipFile = $this->makeFilename())
			->extract($zipFile)
			->cleanUp($zipFile);

		$composer = $this->findComposer();

		$commands = [
			$composer.' install'
		];

		$process = new Process(implode(' && ', $commands), $this->getInstallationDirectory(), null, null, null);
		$process->run(function ($type, $line) use ($output) {
			$output->write($line);
		});

		$output->writeln('<comment>Arastta should be ready.</comment>');
	}

	/**
	 * Verify that the application does not already exist.
	 *
	 */
	protected function verifyApplicationDoesntExist()
	{
		if (is_dir($this->getInstallationDirectory()) && $this->getInstallationDirectory() != getcwd()) {
			throw new RuntimeException('Application already exists!');
		}
	}

	/**
	 * Generate a random temporary filename.
	 *
	 * @return string
	 */
	protected function makeFilename()
	{
		return getcwd().'/arastta_'.md5(time().uniqid()).'.zip';
	}

	/**
	 * Download the temporary Zip to the given file.
	 *
	 * @param  string  $zipFile
	 * @return $this
	 */
	protected function download($zipFile)
	{
		$response = (new Client)->get($this->getDownloadLink());
		file_put_contents($zipFile, $response->getBody());
		return $this;
	}

	/**
	 * Extract the zip file into the given directory.
	 *
	 * @param  string  $zipFile
	 * @return $this
	 */
	protected function extract($zipFile)
	{
		$archive = new ZipArchive;
		$archive->open($zipFile);
		$archive->extractTo($this->getInstallationDirectory());
		$archive->close();
		return $this;
	}

	/**
	 * Clean-up the Zip file.
	 *
	 * @param  string  $zipFile
	 * @return $this
	 */
	protected function cleanUp($zipFile)
	{
		@chmod($zipFile, 0777);
		@unlink($zipFile);

		//the latest version puts it inside another folder
		if ($this->input->getOption('latest')) {

			$unzipped = glob($this->getInstallationDirectory() . DIRECTORY_SEPARATOR . 'arastta*', GLOB_ONLYDIR);

			if (!empty($unzipped[0]) && is_dir($unzipped[0])) {
				$newBasePath = $this->getInstallationDirectory() . DIRECTORY_SEPARATOR;

				foreach (glob($unzipped[0] . DIRECTORY_SEPARATOR . '{,.}*', GLOB_BRACE) as $item) {

					$path = $newBasePath . str_replace($unzipped, '', $item);

					rename($item, $path);
				}

				rmdir($unzipped[0]);
			}

		}

		return $this;
	}

	/**
	 * Get the composer command for the environment.
	 *
	 * @return string
	 */
	protected function findComposer()
	{
		if (file_exists(getcwd().'/composer.phar')) {
			return '"'.PHP_BINARY.'" composer.phar';
		}
		return 'composer';
	}

	protected function getDownloadLink() {

		if ($this->input->getOption('latest')) {

			$release_page_details = json_decode(
				(new Client())->get('https://api.github.com/repos/arastta/arastta/releases/latest')->getBody()
			);

			return $release_page_details->zipball_url;
		}

		return 'https://arastta.org/download.php?version=latest';
	}

	/**
	 * @return string
	 */
	protected function getInstallationDirectory() {
		return ($this->input->getArgument('name')) ? getcwd() . '/' . $this->input->getArgument('name') : getcwd();
	}
}
