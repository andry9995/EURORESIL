<?php

namespace App\Service\PDF;

class PDFExec
{
	/**
	 * @var array
	 */
	private array $commands = [];

	/**
	 * @var string
	 */
	const WINDOWS_OS  = "WIN";

	/**
	 * @var string
	 */
	const DEFAULT_OS  = "LIN";

	function __construct()
	{
		$this->initCommands();
	}

	/**
	 *
	 * Init all command with OS variation
	 *
	 * @return void
	 */
	private function initCommands(): void
    {
		$this->commands = [
			"convert" => [
				self::WINDOWS_OS => "set HOME=%s && soffice --headless --convert-to pdf --outdir %s %s",
			//	self::WINDOWS_OS => "$env:HOME=%s; & soffice --headless --convert-to pdf --outdir %s %s",
				self::DEFAULT_OS => "export HOME=%s && libreoffice --headless --convert-to pdf --outdir %s %s"
			],
			"merge" => [
				self::WINDOWS_OS => "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=%s %s",
				//self::WINDOWS_OS => "gswin64c -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=%s %s",
				self::DEFAULT_OS => "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=%s %s"
			]
		];
	}

	/**
	 *
	 * Get specific command compatible with current OS
	 *
	 * @param string $command
	 * @return string|null
     */
	public function get(string $command): ?string
    {
		if (!array_key_exists($command, $this->commands)) return null;

		$command = $this->commands[$command];

		if (
			strtoupper(substr(PHP_OS, 0, 3)) === self::DEFAULT_OS
			&& !!array_key_exists(self::DEFAULT_OS, $command)
		)
			return $command[self::DEFAULT_OS];

        // the default command is compatible with Windows OS
        return $command[self::WINDOWS_OS];

	}

	/**
	 *
	 * Exec specific command with dynamic variable
	 *
	 * @param string $command
	 * @param array $variables
	 * @return void
	 */
	public function run(string $command, array $variables = array()): void
    {
		$command = vsprintf(
            $this->get($command),
            $variables
        );

        exec($command);
	}
}
