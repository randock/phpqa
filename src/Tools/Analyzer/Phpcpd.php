<?php

namespace Edge\QA\Tools\Analyzer;

use Symfony\Component\Process\Process;

class Phpcpd extends \Edge\QA\Tools\Tool
{
    public static $SETTINGS = array(
        'optionSeparator' => ' ',
        'xml' => ['phpcpd.xml'],
        'errorsXPath' => '//pmd-cpd/duplication',
        'composer' => 'sebastian/phpcpd',
    );

    public function __invoke()
    {
        $version = $this->getVersion();
        $args = array(
            $this->options->ignore->bergmann(),
            $this->options->getAnalyzedDirs(' '),
            'min-lines' => $this->config->value('phpcpd.minLines'),
            'min-tokens' => $this->config->value('phpcpd.minTokens'),
        );
        if (version_compare($version, '6.0.0', '<')) {
            $args['progress'] = '';
        }
        $phpcpdNames = array_map(
            function ($extension) {
                return "*.{$extension}";
            },
            array_filter(explode(',', $this->config->csv('phpqa.extensions')))
        );
        if ($phpcpdNames) {
            $argName = version_compare($this->getVersion(), '6.0.0', '<') ? 'names' : 'suffix';
            $args[$argName] = \Edge\QA\escapePath(implode(',', $phpcpdNames));
        }
        if ($this->options->isSavedToFiles) {
            $args['log-pmd'] = $this->tool->getEscapedXmlFile();
        }
        return $args;
    }

    /**
     * @return string|string[]
     */
    private function getVersion()
    {
        $command = ['phpcpd', '--version'];
        $process = new Process($command);
        $process->run();
        $firstLine = strtok($process->getOutput(), "\n");

        return str_replace(['phpcpd ', ' by Sebastian Bergmann.'], '', $firstLine);
    }
}
