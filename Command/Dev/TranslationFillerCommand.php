<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Command\Dev;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;

class TranslationFillerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('claroline:translation:filler')
            ->setDescription('Search the translations and order them in their different config.yml files');
        $this->setDefinition(
            array(
                new InputArgument('locale', InputArgument::REQUIRED, 'The locale to fill.'),
            )
        );
        $this->addOption(
            'domain',
            null,
            InputOption::VALUE_REQUIRED,
            'Wich domain do you want to fill ?'
        );
        $this->addOption(
            'main_lang',
            null,
            InputOption::VALUE_REQUIRED,
            'Which language already contains every translation ?'
        );
        $this->addOption(
            'fqcn',
            null,
            InputOption::VALUE_REQUIRED,
            'What is the bundle fqcn ?'
        );
        $this->addOption(
            'fill',
            'f',
            InputOption::VALUE_NONE,
            'Override the translations file'
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $params = array(
            'locale' => 'locale to fill: '
        );

        foreach ($params as $argument => $argumentName) {
            if (!$input->getArgument($argument)) {
                $input->setArgument(
                    $argument, $this->askArgument($output, $argumentName)
                );
            }
        }
    }

    protected function askArgument(OutputInterface $output, $argumentName)
    {
        $argument = $this->getHelper('dialog')->askAndValidate(
            $output,
            $argumentName,
            function ($argument) {
                if (empty($argument)) {
                    throw new \Exception('This argument is required');
                }

                return $argument;
            }
        );

        return $argument;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ds = DIRECTORY_SEPARATOR;
        $fqcn = $input->getOption('fqcn') ? $input->getOption('fqcn'): 'ClarolineCoreBundle';
        $domain = $input->getOption('domain') ? $input->getOption('domain'): 'platform';
        $locale = $input->getArgument('locale');
        $mainLang = $input->getOption('main_lang') ? $input->getOption('main_lang'): 'fr';

        $filledShortPath = '@' . $fqcn . '/Resources/translations/' . $domain . '.' . $locale . '.yml';
        $mainShortPath = '@' . $fqcn . '/Resources/translations/' . $domain . '.' . $mainLang . '.yml';
        $mainFile = $this->getContainer()->get('kernel')->locateResource($mainShortPath);
        $filledFile = $this->getContainer()->get('kernel')->locateResource($filledShortPath);

        if ($input->getOption('fill')) $this->fill($mainFile, $filledFile, $output);
        $this->showUntranslated($filledFile, $output, $locale);
    }

    private function showUntranslated($filledFile,  OutputInterface $output, $locale)
    {
        $displayWarning = true;
        $line = 1;
        $translations = Yaml::parse($filledFile);
        $safe = $this->getSafeDubious();

        foreach ($translations as $key => $value) {
            if ($key === $value) {
                if (!in_array($key, $safe[$locale])) {
                    if ($displayWarning) {
                        $output->writeln('<comment> These lines may contain incorrect translations </comment>');
                        $displayWarning = false;
                    }
                    $output->writeln(sprintf('line %s - %s', $line, $key));
                }
            }

            $line++;
        }
    }

    private function fill($mainFile, $filledFile, $output)
    {
        $output->writeln("<comment> Filling the translation file {$filledFile} </comment>");
        $mainTranslations = Yaml::parse($mainFile);
        $translations = Yaml::parse($filledFile);
        if (!$translations) $translations = array();

        //add missing keys
        foreach (array_keys($mainTranslations) as $requiredKey) {
            if (!array_key_exists($requiredKey, $translations)) {
                $translations[$requiredKey] = $requiredKey;
            }
        }

        //removing superfluous keys
        foreach ($translations as $key => $value) {
            if (!array_key_exists($key, $mainTranslations)) unset($translations[$key]);
        }

        ksort($translations);
        $yaml = Yaml::dump($translations);
        file_put_contents($filledFile, $yaml);
    }

    private function getSafeDubious()
    {
        return array(
            'en' => array(
                'by', 'dsn'
            ),
            'fr' => array(
                'dsn'
            ),
            'es' => array(
                'dsn'
            ),
            'nl' => array(

            ),
            'de' => array(

            )
        );
    }
}
