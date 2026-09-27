<?php

declare(strict_types=1);

namespace App\Command;

use App\Pesel\Pesel;
use App\Pesel\PeselMask;
use App\Pesel\Validation\PeselValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:pesel:validate',
    description: 'Validates a PESEL number and prints the data it encodes.',
)]
final class ValidatePeselCommand extends Command
{
    public function __construct(private readonly PeselValidator $validator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('pesel', InputArgument::REQUIRED, 'PESEL number to validate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $argument = $input->getArgument('pesel');
        $raw = is_string($argument) ? $argument : '';

        $result = $this->validator->validate($raw);

        if (!$result->isValid()) {
            $io->error(sprintf('%s is not a valid PESEL number.', PeselMask::mask($raw)));
            $io->listing(array_map(
                static fn ($violation): string => sprintf('[%s] %s', $violation->code->value, $violation->message),
                $result->violations(),
            ));

            return Command::FAILURE;
        }

        $pesel = Pesel::fromString($raw);

        $io->success(sprintf('%s is a valid PESEL number.', $pesel->masked()));
        $io->definitionList(
            ['Date of birth' => $pesel->birthDate()->format('Y-m-d')],
            ['Gender' => $pesel->gender()->value],
        );

        return Command::SUCCESS;
    }
}
