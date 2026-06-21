<?php

namespace App\Service\PDF;

use clsTinyButStrong;

class OpenTBSService
{
	/**
	 * @var clsTinyButStrong
	 */
	private $TBS;

	/**
	 * @var string
	 */
	private $templatePath;

	/**
	 * @var string
	 */
	private $renderPath;

	/**
	 * @var array
	 */
	private $objectRef;

	/**
	 * @var array
	 */
	private $varRef;

	/**
	 * @var array
	 */
	private $fields;

	const CLS_OPEN_TBS = 'clsOpenTBS';

	/**
	 * @param array $options
	 * @return void
	 */
	public function show($options)
	{
		foreach ($options as $property => $value) {
			if (property_exists(__CLASS__, $property)) {
				$this->$property = $value;
			}
		}

		$this->TBS = new clsTinyButStrong();
        $this->TBS->Plugin(TBS_INSTALL, self::CLS_OPEN_TBS);
        $this->TBS->LoadTemplate($this->templatePath, OPENTBS_ALREADY_UTF8);

        if (!empty($this->objectRef)) {
        	foreach ($this->objectRef as $key => $value) {
        		$this->TBS->ObjectRef[$key] = $value;
        	}
        }

        if (!empty($this->fields)) {
        	foreach ($this->fields as $key => $value) {
        		$this->TBS->MergeField($key, $value);
        	}
        }

        if (!empty($this->varRef)) {
        	foreach ($this->varRef as $key => $value) {
        		$this->TBS->VarRef[$key] = $value;
        	}
        }

        $this->TBS->Show(OPENTBS_FILE, $this->renderPath);
	}
}
