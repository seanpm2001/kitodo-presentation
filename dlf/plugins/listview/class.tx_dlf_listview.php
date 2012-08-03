<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Sebastian Meyer <sebastian.meyer@slub-dresden.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * Plugin 'DLF: List View' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_listview extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/listview/class.tx_dlf_listview.php';

	/**
	 * This holds the list
	 *
	 * @var	tx_dlf_list
	 * @access	protected
	 */
	protected $list;

	/**
	 * Array of sorted metadata
	 *
	 * @var	array
	 * @access	protected
	 */
	protected $metadata = array ();

	/**
	 * Renders the page browser
	 *
	 * @access	protected
	 *
	 * @return	string		The rendered page browser ready for output
	 */
	protected function getPagebrowser() {

		// Get overall number of pages.
		$maxPages = intval(ceil($this->list->count / $this->conf['limit']));

		// Return empty pagebrowser if there is just one page.
		if ($maxPages < 2) {

			return '';

		}

		// Get separator.
		$separator = $this->pi_getLL('separator', ' - ');

		// Add link to previous page.
		if ($this->piVars['pointer'] > 0) {

			$output = $this->pi_linkTP_keepPIvars($this->pi_getLL('prevPage', '&lt;'), array ('pointer' => $this->piVars['pointer'] - 1), TRUE).$separator;

		} else {

			$output = $this->pi_getLL('prevPage', '&lt;').$separator;

		}

		$i = 0;

		// Add links to pages.
		while ($i < $maxPages) {

			if ($i < 3 || ($i > $this->piVars['pointer'] - 3 && $i < $this->piVars['pointer'] + 3) || $i > $maxPages - 4) {

				if ($this->piVars['pointer'] != $i) {

					$output .= $this->pi_linkTP_keepPIvars(sprintf($this->pi_getLL('page', '%d'), $i + 1), array ('pointer' => $i), TRUE).$separator;

				} else {

					$output .= sprintf($this->pi_getLL('page', '%d'), $i + 1).$separator;

				}

				$skip = TRUE;

			} elseif ($skip == TRUE) {

				$output .= $this->pi_getLL('skip', '...').$separator;

				$skip = FALSE;

			}

			$i++;

		}

		// Add link to next page.
		if ($this->piVars['pointer'] < $maxPages - 1) {

			$output .= $this->pi_linkTP_keepPIvars($this->pi_getLL('nextPage', '&gt;'), array ('pointer' => $this->piVars['pointer'] + 1), TRUE);

		} else {

			$output .= $this->pi_getLL('nextPage', '&gt;');

		}

		return $output;

	}

	/**
	 * Renders one entry of the list
	 *
	 * @access	protected
	 *
	 * @param	integer		$number: The number of the entry
	 * @param	string		$template: Parsed template subpart
	 *
	 * @return	string		The rendered entry ready for output
	 */
	protected function getEntry($number, $template) {

		$markerArray['###NUMBER###'] = $number + 1;

		$markerArray['###METADATA###'] = '';

		$subpart = '';

		foreach ($this->metadata as $_index_name => $_metaConf) {

			$value = '';

			$fieldwrap = $this->parseTS($_metaConf['wrap']);

			do {

				$_value = array_shift($this->list->elements[$number]['metadata'][$_index_name]);

				// Link title to pageview.
				if ($_index_name == 'title') {

					// Get title of parent document if needed.
					if (empty($_value) && $this->conf['getTitle']) {

						$_value = '['.tx_dlf_document::getTitle($this->list->elements[$number]['uid'], TRUE).']';

					}

					// Set fake title if still not present.
					if (empty($_value)) {

						$_value = $this->pi_getLL('noTitle');

					}

					$_value = $this->pi_linkTP(htmlspecialchars($_value), array ($this->prefixId => array ('id' => $this->list->elements[$number]['uid'], 'page' => $this->list->elements[$number]['page'], 'pointer' => $this->piVars['pointer'])), TRUE, $this->conf['targetPid']);

				// Translate name of holding library.
				} elseif ($_index_name == 'owner' && !empty($_value)) {

					$_value = htmlspecialchars(tx_dlf_helper::translate($_value, 'tx_dlf_libraries', $this->conf['pages']));

				// Translate document type.
				} elseif ($_index_name == 'type' && !empty($_value)) {

					$_value = htmlspecialchars(tx_dlf_helper::translate($_value, 'tx_dlf_structures', $this->conf['pages']));

				// Translate ISO 639 language code.
				} elseif ($_index_name == 'language' && !empty($_value)) {

					$_value = htmlspecialchars(tx_dlf_helper::getLanguageName($_value));

				} elseif (!empty($_value)) {

					$_value = htmlspecialchars($_value);

				}

				$_value = $this->cObj->stdWrap($_value, $fieldwrap['value.']);

				if (!empty($_value)) {

					$value .= $_value;

				}

			} while (count($this->list->elements[$number]['metadata'][$_index_name]));

			if (!empty($value)) {

				$field = $this->cObj->stdWrap(htmlspecialchars($_metaConf['label']), $fieldwrap['key.']);

				$field .= $value;

				$markerArray['###METADATA###'] .= $this->cObj->stdWrap($field, $fieldwrap['all.']);

			}

		}

		if (!empty($this->list->elements[$number]['subparts'])) {

			$subpart = $this->getSubEntries($number, $template);

		}

		return $this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($template['entry'], '###SUBTEMPLATE###', $subpart, TRUE), $markerArray);

	}

	/**
	 * Renders all sub-entries of one entry
	 *
	 * @access	protected
	 *
	 * @param	integer		$number: The number of the entry
	 * @param	string		$template: Parsed template subpart
	 *
	 * @return	string		The rendered entries ready for output
	 */
	protected function getSubEntries($number, $template) {

		$content = '';

		foreach ($this->list->elements[$number]['subparts'] as $subpart) {

			$markerArray['###SUBMETADATA###'] = '';

			foreach ($this->metadata as $_index_name => $_metaConf) {

				$value = '';

				$fieldwrap = $this->parseTS($_metaConf['wrap']);

				do {

					$_value = array_shift($subpart['metadata'][$_index_name]);

					// Link title to pageview.
					if ($_index_name == 'title') {

						// Get title of parent document if needed.
						if (empty($_value) && $this->conf['getTitle']) {

							$_value = '['.tx_dlf_document::getTitle($subpart['uid'], TRUE).']';

						}

						// Set fake title if still not present.
						if (empty($_value)) {

							$_value = $this->pi_getLL('noTitle');

						}

						$_value = $this->pi_linkTP(htmlspecialchars($_value), array ($this->prefixId => array ('id' => $subpart['uid'], 'page' => $subpart['page'], 'pointer' => $this->piVars['pointer'])), TRUE, $this->conf['targetPid']);

					// Translate name of holding library.
					} elseif ($_index_name == 'owner' && !empty($_value)) {

						$_value = htmlspecialchars(tx_dlf_helper::translate($_value, 'tx_dlf_libraries', $this->conf['pages']));

					// Translate document type.
					} elseif ($_index_name == 'type' && !empty($_value)) {

						$_value = $this->pi_getLL($_value, tx_dlf_helper::translate($_value, 'tx_dlf_structures', $this->conf['pages']), FALSE);

					// Translate ISO 639 language code.
					} elseif ($_index_name == 'language' && !empty($_value)) {

						$_value = htmlspecialchars(tx_dlf_helper::getLanguageName($_value));

					} elseif (!empty($_value)) {

						$_value = htmlspecialchars($_value);

					}

					$_value = $this->cObj->stdWrap($_value, $fieldwrap['value.']);

					if (!empty($_value)) {

						$value .= $_value;

					}

				} while (count($subpart['metadata'][$_index_name]));

				if (!empty($value)) {

					$field = $this->cObj->stdWrap(htmlspecialchars($_metaConf['label']), $fieldwrap['key.']);

					$field .= $value;

					$markerArray['###SUBMETADATA###'] .= $this->cObj->stdWrap($field, $fieldwrap['all.']);

				}

			}

			$content .= $this->cObj->substituteMarkerArray($template['subentry'], $markerArray);

		}

		return $this->cObj->substituteSubpart($this->cObj->getSubpart($this->template, '###SUBTEMPLATE###'), '###SUBENTRY###', $content, TRUE);

	}

	/**
	 * Get metadata configuration from database
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function loadConfig() {

		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.wrap AS wrap',
			'tx_dlf_metadata',
			'tx_dlf_metadata.is_listed=1 AND tx_dlf_metadata.pid='.intval($this->conf['pages']).tx_dlf_helper::whereClause('tx_dlf_metadata'),
			'',
			'tx_dlf_metadata.sorting ASC',
			''
		);

		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result)) {

			$this->metadata[$resArray['index_name']] = array (
				'wrap' => $resArray['wrap'],
				'label' => tx_dlf_helper::translate($resArray['index_name'], 'tx_dlf_metadata', $this->conf['pages'])
			);

		}

	}

	/**
	 * The main method of the PlugIn
	 *
	 * @access	public
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	string		The content that is displayed on the website
	 */
	public function main($content, $conf) {

		$this->init($conf);

		// Don't cache the output.
		$this->setCache(FALSE);

		// Load the list.
		$this->list = t3lib_div::makeInstance('tx_dlf_list');

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/listview/template.tmpl'), '###TEMPLATE###');

		}

		$subpartArray['entry'] = $this->cObj->getSubpart($this->template, '###ENTRY###');

		$subpartArray['subentry'] = $this->cObj->getSubpart($this->template, '###SUBENTRY###');

		// Set some variable defaults.
		if (!empty($this->piVars['pointer']) && (($this->piVars['pointer'] * $this->conf['limit']) + 1) <= $this->list->count) {

			$this->piVars['pointer'] = max(intval($this->piVars['pointer']), 0);

		} else {

			$this->piVars['pointer'] = 0;

		}

		$this->loadConfig();

		for ($i = $this->piVars['pointer'] * $this->conf['limit'], $j = ($this->piVars['pointer'] + 1) * $this->conf['limit']; $i < $j; $i++) {

			if (empty($this->list->elements[$i])) {

				break;

			} else {

				$content .= $this->getEntry($i, $subpartArray);

			}

		}

		$markerArray['###LISTTITLE###'] = $this->list->metadata['label'];

		$markerArray['###LISTDESCRIPTION###'] = $this->list->metadata['description'];

		if ($i) {

			$markerArray['###COUNT###'] = htmlspecialchars(sprintf($this->pi_getLL('count'), ($this->piVars['pointer'] * $this->conf['limit']) + 1, $i, $this->list->count));

		} else {

			$markerArray['###COUNT###'] = $this->pi_getLL('nohits', '', TRUE);

		}

		$markerArray['###PAGEBROWSER###'] = $this->getPageBrowser();

		$content = $this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($this->template, '###ENTRY###', $content, TRUE), $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/listview/class.tx_dlf_listview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/listview/class.tx_dlf_listview.php']);
}

?>