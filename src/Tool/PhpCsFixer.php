<?php

namespace Edge\QA\Tool;

use Edge\QA\OutputMode;

class PhpCsFixer extends Tool
{
    public static $SETTINGS = array(
        'optionSeparator' => ' ',
        'internalClass' => 'PhpCsFixer\Config',
        'outputMode' => OutputMode::XML_CONSOLE_OUTPUT,
        'composer' => 'friendsofphp/php-cs-fixer',
        'xml' => ['php-cs-fixer.xml'],
        'errorsXPath' => '//testsuites/testsuite/testcase/failure',
    );

    public function __invoke()
    {
        $configFile = $this->config->value('php-cs-fixer.config');
        if ($configFile) {
            $analyzedDir = $this->options->getAnalyzedDirs(' ');
        } else {
            $analyzedDirs = $this->options->getAnalyzedDirs();
            $analyzedDir = reset($analyzedDirs);
            if (count($analyzedDirs) > 1) {
                $this->say("<error>php-cs-fixer analyzes only first directory {$analyzedDir}</error>");
                $this->say(
                    "- <info>multiple dirs are supported if you specify " .
                    "<comment>php-cs-fixer.config</comment> in <comment>.phpqa.yml</comment></info>"
                );
            }
        }
        $args = [
            'fix',
            $analyzedDir,
            'verbose' => '',
            'format' => $this->options->isSavedToFiles ? 'junit' : 'txt',
        ];
        if ($configFile) {
            $args['config'] = $configFile;
        } else {
            $args += [
                'rules' => $this->config->value('php-cs-fixer.rules'),
                'allow-risky' => $this->config->value('php-cs-fixer.allowRiskyRules') ? 'yes' : 'no',
            ];
        }
        if ($this->config->value('php-cs-fixer.isDryRun')) {
            $args['dry-run'] = '';
        }
        return $args;
    }
}