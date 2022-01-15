<?php

global $Wcms;

$Wcms->addListener('settings', function ($args) {
    global $Wcms;

    $doc = new DOMDocument();
    @$doc->loadHTML($args[0]);

    /* Input element for header height */

    $label = $doc->createElement('p');
    $label->setAttribute('class', 'subTitle');
    $label->nodeValue = 'SEO';

    $doc->getElementById('menu')->insertBefore($label, $doc->getElementById('menu')->lastChild);

    $wrapper = $doc->createElement('div');
    $wrapper->setAttribute('class', 'change');

    $form = $doc->createElement('form');
    $form->setAttribute('action', $Wcms->url());
    $form->setAttribute('method', 'post');

    $button = $doc->createElement('input');
    $button->setAttribute('type', 'submit');
    $button->setAttribute('class', 'btn btn-info');
    $button->setAttribute('value', 'Update Sitemap');
    $button->setAttribute('name', 'sitemap');
    $form->appendChild($button);

    $token = $doc->createElement('input');
    $token->setAttribute('type', 'hidden');
    $token->setAttribute('value', $Wcms->getToken());
    $token->setAttribute('name', 'token');
    $form->appendChild($token);

    $wrapper->appendChild($form);

    $doc->getElementById('menu')->insertBefore($wrapper, $doc->getElementById('menu')->lastChild);

    $args[0] = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $doc->saveHTML());

    if (! isset($_POST['sitemap']) || ! $Wcms->verifyFormActions()) {
        return $args;
    }

    $BASE_URL = $Wcms->url();

    $output = [$BASE_URL];

    // CMS pages
    foreach ($Wcms->get('config', 'menuItems') as $item) {
        if ($item->visibility === 'hide') {
            continue;
        }
        $output[] = $BASE_URL . $item->slug;
    }

    // Blog pages
    if (is_dir(__DIR__  . '/../simple-blog')) {
        try {
            include_once __DIR__ . '/../simple-blog/class.SimpleBlog.php';
            $Blog = new SimpleBlog(false);
            $Blog->init();

            foreach ($Blog->get('posts') as $slug => $item) {
                $output[] = $BASE_URL . $Blog->slug . '/' . $slug;
            }
        } catch (Exception $e) {
            // Fail gracefully, probably different plugin with same name
        }
    }

    // Store pages
    if (is_dir(__DIR__ . '/../simple-store')) {
        // Todo: add store items
    }

    $output[] = '';

    $sitemap = join("\n", $output);

    $robots = <<<TXT
User-agent: *
Allow: /

Sitemap: {$Wcms->url('sitemap.txt')}

TXT;

    file_put_contents(__DIR__ . '/../../sitemap.txt', $sitemap);
    file_put_contents(__DIR__ . '/../../robots.txt', $robots);

    $Wcms->alert('success', 'Updated sitemap - robots.txt and sitemap.txt generated.');

    return $args;
});
