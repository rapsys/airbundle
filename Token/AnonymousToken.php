<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * Anonymous Token
 *
 * {@inheritdoc}
 */
class AnonymousToken extends AbstractToken {
}
