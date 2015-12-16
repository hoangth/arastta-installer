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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use ZipArchive;

class CreateCommand extends Command {

	private $remoteZip = 'https://github.com/mattythebatty/arastta-cli/archive/master.zip';

	public function configure() {

		$this->setName('create')
			->setDescription('Create a new Arastta instance into the project directory specified')
			->addArgument('name', InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$this->verifyApplicationDoesntExist(
			$directory = getcwd().'/'.$input->getArgument('name'),
			$output
		);

		$output->writeln('<info>Creating Arastta Installation...</info>');

		$this->download($zipFile = $this->makeFilename())
			->extract($zipFile, $directory)
			->cleanUp($zipFile);

		$composer = $this->findComposer();

		$commands = [
			$composer.' install'
		];
		$process = new Process(implode(' && ', $commands), $directory, null, null, null);
		$process->run(function ($type, $line) use ($output) {
			$output->write($line);
		});
		$output->writeln('<comment>Arastta should be ready.</comment>');
	}

	/**
	 * Verify that the application does not already exist.
	 *
	 * @param  string $directory
	 * @param OutputInterface $output
	 */
	protected function verifyApplicationDoesntExist($directory, OutputInterface $output)
	{
		if (is_dir($directory)) {
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
		$response = (new Client)->get($this->remoteZip);
		file_put_contents($zipFile, $response->getBody());
		return $this;
	}
	/**
	 * Extract the zip file into the given directory.
	 *
	 * @param  string  $zipFile
	 * @param  string  $directory
	 * @return $this
	 */
	protected function extract($zipFile, $directory)
	{
		$archive = new ZipArchive;
		$archive->open($zipFile);
		$archive->extractTo($directory);
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
}