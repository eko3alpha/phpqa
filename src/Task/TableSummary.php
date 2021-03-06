<?php

namespace Edge\QA\Task;

use Edge\QA\Options;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

class TableSummary
{
    private $options;
    private $output;
    
    public function __construct(Options $o, OutputInterface $p)
    {
        $this->options = $o;
        $this->output = $p;
    }

    /**
     * @param \Edge\QA\RunningTool[] $usedTools
     * @return int
     */
    public function __invoke(array $usedTools)
    {
        $this->writeln('', 'cyan');
        $table = new Table($this->output);
        $table->setHeaders(array('Tool', 'Allowed Errors', 'Errors count', 'Is OK?', 'HTML report'));
        $totalErrors = 0;
        $failedTools = [];
        foreach ($usedTools as $tool) {
            list($isOk, $errorsCount) = $tool->analyzeResult();
            $totalErrors += $errorsCount;
            $table->addRow(array(
                "<comment>{$tool}</comment>",
                $tool->getAllowedErrorsCount(),
                $errorsCount,
                $this->getStatus($isOk),
                $tool->htmlReport
            ));
            if (!$isOk) {
                $failedTools[] = (string) $tool;
            }
        }
        $table->addRow(new TableSeparator());
        $table->addRow(array(
            '<comment>phpqa</comment>',
            '',
            $failedTools ? "<error>{$totalErrors}</error>" : $errorsCount,
            $this->getStatus(empty($failedTools)),
            $this->options->hasReport ? $this->options->rawFile("phpqa.html") : ''
        ));
        $table->render();
        return $this->result($failedTools);
    }

    private function result(array $failedTools)
    {
        if ($failedTools) {
            $this->writeln('Failed tools: <comment>' . implode(', ', $failedTools) . '</comment>', 'red');
            return 1;
        } else {
            $this->writeln('No failed tools', 'green');
            return 0;
        }
    }

    private function getStatus($isOk)
    {
        return $isOk ? '<info>✓</info>' : '<error>x</error>';
    }

    // copy-paste from \Robo\Common\TaskIO
    private function writeln($text, $color)
    {
        $this->output->writeln(
            "\n<fg=white;bg={$color};options=bold>[phpqa]</fg=white;bg={$color};options=bold> {$text}"
        );
    }
}
