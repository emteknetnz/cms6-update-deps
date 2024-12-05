<?php

$s = <<<EOT
asyncphp/doorman:^4
- 4.0.0
aws/aws-sdk-php:^3.281
- 3.334.1
behat/behat:^3.11.0
- 3.16.0
behat/mink:^1.10.0
- 1.12.0
composer-plugin-api:^2
- do not change
composer-runtime-api:^2.0
- do not change
composer/composer:^2
- 2.8.3
ccomposer/installers:*
- 2.3.0
composer/installers:^2
- 2.3.0
composer/installers:^2.2
- 2.3.0
composer/semver:^3.4
- 3.4.3
defuse/php-encryption:^2.3
- 2.4.0
dragonmantank/cron-expression:^3
- 3.4.0
embed/embed:^4.4.7
- 4.4.14
friends-of-behat/mink-extension:^2
- 2.7.5
fzaninotto/faker:^1.9.2
- 1.9.2
guzzlehttp/guzzle:^7.5
- 7.9.2
guzzlehttp/guzzle:^7.5.0
- 7.9.2
guzzlehttp/psr7:^2.4.0
- 2.7.0
guzzlehttp/psr7:^2.4.1
- 2.7.0
intervention/image:^3.7
- 3.9.1
jeremeamia/superclosure:^2.0
- 2.4.0
league/commonmark:^2.4
- 2.5.3
league/csv:^9
- 9.18.0
league/csv:^9.8.0
- 9.18.0
league/flysystem-local:^3.22
- 3.29.0
league/flysystem:^3.22
- 3.29.1
m1/env:^2.2.0
- 2.2.0
marcj/topsort:^2.0.0
- 2.0.0
masterminds/html5:^2.7.6
- 2.9.0
mikey179/vfsstream:^1.6
- 1.6.12
mikey179/vfsstream:^1.6.11
- 1.6.12
mikey179/vfsstream:^v1.6.11
- 1.6.12
monolog/monolog:^3.2.0
- 3.8.0
nikic/php-parser:^5.1.0
- 5.3.1
onelogin/php-saml:^4
- 4.2.0
paragonie/constant_time_encoding:^2.6
- ! 3.0.0
php-parallel-lint/php-parallel-lint:^1
- 1.4.0
php-webdriver/webdriver:^1.13.1
- 1.15.2
php:^8.0
- ^8.3
php:^8.1
- ^8.3
php:^8.3
- ^8.3
phpstan/extension-installer:^1.3
- 1.4.3
phpunit/phpunit:^11.3
- 11.4.4
phpunit/phpunit:^4.0 || ^5.0 || ^6.0 || ^7.0 || ^8.0 || ^9.0
- 11.4.4
phpunit/phpunit:^9.6
- 11.4.4
psr/container:^1.1 || ^2.0
- 2.0.2
psr/event-dispatcher:^1
- 1.0.0
psr/http-message:^1
- ! 2.0
psr/simple-cache:^3.0.0
- 3.0.0
sebastian/diff:^6.0
- 6.0.2
sensiolabs/ansi-to-html:^1.2
- 1.2.1
silverstripe-themes/simple:3.x-dev
- 3.3.2
slevomat/coding-standard:^8.14
- 8.15.0
sminnee/callbacklist:^0.1.1
- 0.1.1
spomky-labs/otphp:^11.1
- 11.3.0
squizlabs/php_codesniffer:^3
- 3.7.2
squizlabs/php_codesniffer:^3.7
- 3.7.2
ua-parser/uap-php:^3.9.14
- 3.9.14
webonyx/graphql-php:^15.0.1
- 15.19.0
EOT;

$lines = explode("\n", trim($s));
$arr = [];
$last = '';
foreach ($lines as $line) {
    if (str_starts_with($line, '-')) {
        $new = str_replace('- ', '', $line);
        if ($new == 'do not change') {
            continue;
        }
        if (str_contains($new, '!')) {
            continue;
        }
        if (str_contains($last, 'phpunit')) {
            continue;
        }
        $replace = "^" . preg_replace('/(\d+\.\d+)\.\d+/', '$1', $new);
        $replace = str_replace('^^', '^', $replace);
        [$name, $version] = explode(':', $last);
        // $find = "\"$name\": \"$ver\"";
        $arr[] = [
            'name' => $name,
            'old_version' => $version,
            'new_version' => $replace,
        ];
    } else {
        $last = $line;
        continue;
    }
}

$deps = [];
$vendors = ["silverstripe","symbiote","tractorcow","colymba","dnadesign"];
foreach ($vendors as $vendor) {
    $files = shell_exec("find vendor/$vendor/. | grep composer.json");
    $files = explode("\n", $files);
    foreach ($files as $file) {
        if (!$file) continue;
        if (str_contains($file, "/tinymce/")) continue;
        if (preg_match("#/tests/#", $file)) continue;
        if (str_contains($file, "webauthn-authenticator")) continue;
        $c = file_get_contents($file);
        $j = json_decode($c, true);
        foreach (["require", "require-dev"] as $r) {
            if (!isset($j[$r])) continue;
            foreach ($j[$r] as $k => $v) {
                if (str_starts_with($k, "silverstripe/")) continue;
                if (str_starts_with($k, "bringyourownideas/")) continue;
                if (str_starts_with($k, "colymba/")) continue;
                if (str_starts_with($k, "dnadesign/")) continue;
                if (str_starts_with($k, "symbiote/")) continue;
                if (str_starts_with($k, "symfony/")) continue;
                if (str_starts_with($k, "ext-")) continue;
                // $deps["$k:$v"] = true;
                $name = $k;
                $old_version = $v;
                foreach ($arr as $a) {
                    if ($a['name'] == $name && $a['old_version'] == $old_version) {
                        $new_version = $a['new_version'];
                        // $j[$r][$name] = $new_version;
                        $c = str_replace("\"$name\": \"$old_version\"", "\"$name\": \"$new_version\"", $c);
                    }
                }
            }
        }
        file_put_contents($file, $c);
        // echo "$file\n";
    }
}
// ksort($deps);
// foreach ($deps as $dep => $b) {
//   echo "$dep\n";
// }
