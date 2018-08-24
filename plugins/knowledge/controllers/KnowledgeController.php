<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

class KnowledgeController extends VanillaController {
    public function initialize() {
        parent::initialize();
        $this->Application = 'knowledge';
    }

    public function index() {
        $loader = new Twig_Loader_Filesystem(PATH_ROOT.'/plugins/knowledge/views');
        $twig = new Twig_Environment($loader);
        die($twig->render('default.master.twig', [
            'meta' => [
                'title' => 'Knowledge Base Title',
                'locale' => 'en',
            ],

        ]));
    }
}
