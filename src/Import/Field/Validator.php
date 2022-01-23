<?php

declare(strict_types=1);

/*
 * This file is part of Import From CSV Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/import-from-csv-bundle
 */

namespace Markocupic\ImportFromCsvBundle\Import\Field;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Widget;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

class Validator
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator, Connection $connection)
    {
        $this->framework = $framework;
        $this->translator = $translator;
        $this->connection = $connection;
    }

    public function validate(Widget $widget, array $arrDca): void
    {
        $rgxp = $arrDca['eval']['rgxp'] ?? null;

        if (!$rgxp || empty($value)) {
            return;
        }

        $validatorAdapter = $this->framework->getAdapter(\Contao\Validator::class);

        if ('date' === $rgxp || 'datim' === $rgxp || 'time' === $rgxp) {
            if (!$validatorAdapter->{'is'.ucfirst($rgxp)}($value)) {
                $widget->addError(
                    sprintf(
                        $this->translator->trans('ERR.invalidDate', [], 'contao_default'),
                        $widget->value,
                    )
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    public function checkIsUnique(Widget $objWidget, array $arrDca): void
    {
        // Make sure that unique fields are unique
        if (isset($arrDca['eval']['unique']) && true === $arrDca['eval']['unique']) {
            $value = $objWidget->value;

            if ('' !== $value) {
                $query = sprintf(
                    'SELECT id FROM %s WHERE %s = ?',
                    $objWidget->strTable,
                    $objWidget->strField,
                );

                if ($this->connection->fetchOne($query, [$value])) {
                    $objWidget->addError(
                        sprintf(
                            $this->translator->trans('ERR.unique', [], 'contao_default'),
                            $objWidget->strField,
                        )
                    );
                }
            }
        }
    }
}