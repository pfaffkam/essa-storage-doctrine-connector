<?php

namespace PfaffKIT\Essa\Adapters\Storage\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use PfaffKIT\Essa\Adapters\Storage\Entity\DoctrineEvent;

#[
    ORM\Entity,
    ORM\Table(name: 'test_events')
]
readonly class TestEvent extends DoctrineEvent {}
