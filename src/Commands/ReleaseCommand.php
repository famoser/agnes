<?php

namespace Agnes\Commands;

use Agnes\Actions\AbstractPayload;
use Agnes\Actions\PayloadFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReleaseCommand extends AgnesCommand
{
    public function configure()
    {
        $this->setName('release')
            ->setDescription('Create a new release')
            ->setHelp('This command compiles the specified commitish and then publishes it to github.')
            ->addArgument('release', InputArgument::REQUIRED, 'name of the release')
            ->addArgument('commitish', InputArgument::REQUIRED, 'branch or commit of the release');

        parent::configure();
    }

    /**
     * @return AbstractPayload[]
     *
     * @throws \Exception
     */
    protected function createPayloads(InputInterface $input, SymfonyStyle $io, PayloadFactory $payloadFactory): array
    {
        $release = $input->getArgument('release');
        $commitish = $input->getArgument('commitish');

        $payload = $payloadFactory->createRelease($commitish, $release);
        if (null === $payload) {
            return [];
        }

        return [$payload];
    }
}
