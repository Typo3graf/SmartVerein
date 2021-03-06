<?php
/**
 * **************************************************************
 *   Copyright notice
 *
 *   (c) 2018 Typo3graf Developer-Team <development@typo3graf.de>
 *   All rights reserved
 *
 *  This file is part of the TYPO3 CMS project.
 *
 *  It is free software; you can redistribute it and/or modify it under
 *  the terms of the GNU General Public License, either version 2
 *  of the License, or any later version.
 *
 *  For the full copyright and license information, please read the
 *  LICENSE.txt file that was distributed with this source code.
 *
 *  The TYPO3 project - inspiring people to share!
 *
 *   This script is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */

namespace Typo3graf\VmBase\ViewHelpers\Form;

/**
 * Class MultiuploadViewHelper
 */
class MultiuploadViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\UploadViewHelper {

	/**
	 * @var \TYPO3\CMS\Extbase\Property\PropertyMapper
	 * @inject
	 */
	protected $propertyMapper;

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $resources
	 * @return string
	 */
	public function render($resources = NULL) {
		$name = $this->getName();
		foreach (array('type', 'tmp_name', 'error', 'size') as $fieldName) {
			$this->registerFieldNameForFormTokenGeneration(sprintf('%s[*][%s]', $name, $fieldName));
		}
		$this->tag->addAttribute('type', 'file');
		$this->tag->addAttribute('name', $name . '[]');
		$this->tag->addAttribute('multiple', '1');
		$this->setErrorClassAttribute();
		$output = $this->tag->render();

		// File uploads and already persisted uploads are merged into the same
		// array in the property mapper. The uploaded files start at index 0,
		// so we avoid collisions by assigning the indexes for persisted
		// file references by ourselves, starting at a high number.
		$this->viewHelperVariableContainer->add('Typo3graf\\VmBase\\ViewHelpers\\Form\\MultiuploadViewHelper', 'fileReferenceIndex', 10000);
		if ($resources != NULL) {
			$output .= $this->renderChildren();
		} else {
			$this->templateVariableContainer->add($resources, $this->getUploadedResources());
			$output .= $this->renderChildren();
			$this->templateVariableContainer->remove($resources);
		}
		$this->viewHelperVariableContainer->remove('Typo3graf\\VmBase\\ViewHelpers\\Form\\MultiuploadViewHelper', 'fileReferenceIndex');

		return $output;
	}

	/**
	 * Return all resources that are already stored in the file system. This includes
	 * file references that are already persisted and files that were uploaded, but
	 * are not persisted yet because a mapping/validation error occured.
	 *
	 * @return array<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
	 */
	protected function getUploadedResources() {
		// TODO: If a mapping error occurs and Extbase redirects to the edit action,
		// the properties of the form object still contain the mapped values, not
		// the persisted ones! So if you unceck an image in the Multiupload.Delete
		// viewhelper, it disappears from the property and we lose it here as well.
		// This means: we need another way to remember which file references were
		// rendered the first time somewhere ...
		$resources = $this->getPropertyValue();
		if ($resources instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage || $resources instanceof \TYPO3\CMS\Extbase\Persistence\QueryResultInterface) {
			$resources = $resources->toArray();
		} else if (!is_array($resources)) {
			$resources = array();
		}

		if ($this->hasMappingErrorOccurred()) {
			foreach ($this->getLastSubmittedFormData() as $lastSubmittedRecord) {
				$convertedResource = $this->propertyMapper->convert($lastSubmittedRecord, 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FileReference');
				if ($convertedResource !== NULL && !in_array($convertedResource, $resources)) {
					$resources[] = $convertedResource;
				}
			}
		}
		return $resources;
	}

}
