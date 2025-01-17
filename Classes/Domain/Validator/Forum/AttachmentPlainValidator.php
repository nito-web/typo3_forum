<?php
namespace Mittwald\Typo3Forum\Domain\Validator\Forum;

/*                                                                    - *
*  COPYRIGHT NOTICE                                                    *
*                                                                      *
*  (c) 2015 Mittwald CM Service GmbH & Co KG                           *
*           All rights reserved                                        *
*                                                                      *
*  This script is part of the TYPO3 project. The TYPO3 project is      *
*  free software; you can redistribute it and/or modify                *
*  it under the terms of the GNU General Public License as published   *
*  by the Free Software Foundation; either version 2 of the License,   *
*  or (at your option) any later version.                              *
*                                                                      *
*  The GNU General Public License can be found at                      *
*  http://www.gnu.org/copyleft/gpl.html.                               *
*                                                                      *
*  This script is distributed in the hope that it will be useful,      *
*  but WITHOUT ANY WARRANTY; without even the implied warranty of      *
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the       *
*  GNU General Public License for more details.                        *
*                                                                      *
*  This copyright notice MUST APPEAR in all copies of the script!      *
*                                                                      */

use Mittwald\Typo3Forum\Domain\Model\Forum\Attachment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class AttachmentPlainValidator extends AbstractValidator
{
    /**
    * Check if $value is valid. If it is not valid, needs to add an error
    * to Result.
    *
    * @param $value
    *
    * @return bool
    */
    public function isValid($value)
    {
        $result = true;
        $attachmentObj = GeneralUtility::makeInstance(Attachment::class);
        foreach ($value as $attachment) {
            if (empty($attachment['name'])) {
                continue;
            }
            if (!in_array($attachment['type'], $attachmentObj->getAllowedMimeTypes())) {
                $this->addError('The submitted mime-type is not allowed!.', 1371041777);
                $result = false;
            }
            if ($attachment['size'] > $attachmentObj->getAllowedMaxSize()) {
                $this->addError('The submitted file is to big!.', 1371041888);
                $result = false;
            }
        }

        return $result;
    }
}
