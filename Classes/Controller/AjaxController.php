<?php
namespace Mittwald\Typo3Forum\Controller;

/*                                                                      *
 *  COPYRIGHT NOTICE                                                    *
 *                                                                      *
 *  (c) 2015 Mittwald CM Service GmbH & Co KG                           *
 *           All rights reserved                                        *
 *                                                                      *
 *  This script is part of the TYPO3 project. The TYPO3 project is      *
 *  free software; you can redistribute it and/or modify                *
 *  it under the terms of the GNU General Public License as published   *
 *  by the Free Software Foundation; either version 2 of the License,   *
 *  or (at your option) any later version.                              *
 *                                                                      *
 *  The GNU General Public License can be found at                      *
 *  http://www.gnu.org/copyleft/gpl.html.                               *
 *                                                                      *
 *  This script is distributed in the hope that it will be useful,      *
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of      *
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the       *
 *  GNU General Public License for more details.                        *
 *                                                                      *
 *  This copyright notice MUST APPEAR in all copies of the script!      *
 *                                                                      */

use Mittwald\Typo3Forum\Configuration\ConfigurationBuilder;
use Mittwald\Typo3Forum\Domain\Factory\Forum\PostFactory;
use Mittwald\Typo3Forum\Domain\Model\Forum\Post;
use Mittwald\Typo3Forum\Domain\Repository\Forum\AttachmentRepository;
use Mittwald\Typo3Forum\Domain\Repository\Forum\ForumRepository;
use Mittwald\Typo3Forum\Domain\Repository\Forum\PostRepository;
use Mittwald\Typo3Forum\Domain\Repository\Forum\TopicRepository;
use Mittwald\Typo3Forum\Service\AttachmentService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

// TODO: Reimplement Ajax
class AjaxController extends AbstractController
{
    protected AttachmentRepository $attachmentRepository;
    protected ForumRepository $forumRepository;
    protected ConfigurationBuilder $configurationBuilder;
    protected PostFactory $postFactory;
    protected PostRepository $postRepository;
    protected TopicRepository $topicRepository;
    protected AttachmentService $attachmentService;

    public function __construct(
        AttachmentRepository $attachmentRepository,
        ForumRepository $forumRepository,
        ConfigurationBuilder $configurationBuilder,
        PostFactory $postFactory,
        PostRepository $postRepository,
        TopicRepository $topicRepository,
        AttachmentService $attachmentService
    ) {
        $this->attachmentRepository = $attachmentRepository;
        $this->forumRepository = $forumRepository;
        $this->configurationBuilder = $configurationBuilder;
        $this->postFactory = $postFactory;
        $this->postRepository = $postRepository;
        $this->topicRepository = $topicRepository;
        $this->attachmentService = $attachmentService;
    }

    public function initializeObject()
    {
        $this->settings = $this->configurationBuilder->getSettings();
    }

    /**
     * @param string $displayedUser
     * @param string $postSummarys
     * @param string $topicIcons
     * @param string $forumIcons
     * @param string $displayedTopics
     * @param int $displayOnlinebox
     * @param string $displayedPosts
     * @param string $displayedForumMenus
     */
    public function mainAction($displayedUser = '', $postSummarys = '', $topicIcons = '', $forumIcons = '', $displayedTopics = '', $displayOnlinebox = 0, $displayedPosts = '', $displayedForumMenus = '')
    {
        // json array
        $content = [];
        if (!empty($displayedUser)) {
            $content['onlineUser'] = $this->_getOnlineUser($displayedUser);
        }
        if (!empty($displayedForumMenus)) {
            $content['forumMenus'] = $this->_getForumMenus($displayedForumMenus);
        }
        if (!empty($postSummarys)) {
            $content['postSummarys'] = $this->_getPostSummarys($postSummarys);
        }
        if (!empty($topicIcons)) {
            $content['topicIcons'] = $this->_getTopicIcons($topicIcons);
        }
        if (!empty($forumIcons)) {
            $content['forumIcons'] = $this->_getForumIcons($forumIcons);
        }
        if (!empty($displayedTopics)) {
            $content['topics'] = $this->_getTopics($displayedTopics);
        }
        if (!empty($displayedPosts)) {
            $content['posts'] = $this->_getPosts($displayedPosts);
        }
        if ($displayOnlinebox == 1) {
            $content['onlineBox'] = $this->_getOnlinebox();
        }

        $this->view->assign('content', json_encode($content));
    }

    public function loginboxAction()
    {
        $this->view->assign('user', $this->getCurrentUser());
    }

    /**
     * @return array
     */
    private function _getOnlinebox()
    {
        $data = [];
        $data['count'] = $this->frontendUserRepository->countByFilter(true);
        $this->request->setFormat('html');
        $users = $this->frontendUserRepository->findByFilter((int)$this->settings['maxItems'], null, true);
        $this->view->assign('users', $users);
        $data['html'] = $this->view->render('Onlinebox');
        $this->request->setFormat('json');
        return $data;
    }

    /**
     * @param string $displayedForumMenus
     * @return array
     */
    private function _getForumMenus($displayedForumMenus)
    {
        $data = [];
        $displayedForumMenus = json_decode($displayedForumMenus);
        if (count($displayedForumMenus) < 1) {
            return $data;
        }

        $extbaseSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'Typo3Forum');
        $templateRootPaths = $extbaseSettings['view']['templateRootPaths'];

        /* @var StandaloneView $standaloneView */
        $standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
        $standaloneView->setControllerContext($this->controllerContext);
        $standaloneView->setTemplateRootPaths($templateRootPaths);
        $standaloneView->getRenderingContext()->setControllerName('Ajax');
        $standaloneView->setTemplate('forumMenu');
        $standaloneView->setFormat('html');

        $foren = $this->forumRepository->findByUids($displayedForumMenus);
        $counter = 0;
        foreach ($foren as $forum) {
            $standaloneView->assignMultiple([
                'forum' => $forum,
                'user' => $this->getCurrentUser()
            ]);
            $data[$counter]['uid'] = $forum->getUid();
            $data[$counter]['html'] = $standaloneView->render();
            $counter++;
        }
        return $data;
    }

    /**
     * @param string $displayedPosts
     * @return array
     */
    private function _getPosts($displayedPosts)
    {
        $data = [];
        $displayedPosts = json_decode($displayedPosts);
        if (count($displayedPosts) < 1) {
            return $data;
        }
        $this->request->setFormat('html');
        $posts = $this->postRepository->findByUids($displayedPosts);
        $counter = 0;

        $extbaseSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'Typo3Forum');
        $templateRootPaths = $extbaseSettings['view']['templateRootPaths'];

        foreach ($posts as $post) {
            /* @var StandaloneView $standaloneView */
            $standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
            $standaloneView->setControllerContext($this->controllerContext);
            $standaloneView->setTemplateRootPaths($templateRootPaths);
            $standaloneView->getRenderingContext()->setControllerName('Ajax');
            $standaloneView->setTemplate('PostHelpfulButton');
            $standaloneView->setFormat('html');
            /** @var Post $post */
            $standaloneView->assign('settings', $extbaseSettings['settings'])->assign('post', $post)
                ->assign('user', $this->getCurrentUser());

            $data[$counter]['uid'] = $post->getUid();
            $data[$counter]['postHelpfulButton'] = $standaloneView->render();
            $data[$counter]['postHelpfulCount'] = $post->getHelpfulCount();
            $data[$counter]['postUserHelpfulCount'] = $post->getAuthor()->getHelpfulCount();
            $data[$counter]['author']['uid'] = $post->getAuthor()->getUid();

            /* @var StandaloneView $standaloneView */
            $standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
            $standaloneView->setControllerContext($this->controllerContext);
            $standaloneView->setTemplateRootPaths($templateRootPaths);
            $standaloneView->getRenderingContext()->setControllerName('Ajax');
            $standaloneView->setTemplate('PostEditLink');
            $standaloneView->setFormat('html');
            $standaloneView->assign('settings', $extbaseSettings['settings'])->assign('post', $post)
                ->assign('user', $this->getCurrentUser());
            $data[$counter]['postEditLink'] = $standaloneView->render();
            $counter++;
        }
        //$this->request->setFormat('json');
        return $data;
    }

    /**
     * @param string $displayedTopics
     * @return array
     */
    private function _getTopics($displayedTopics)
    {
        $data = [];
        $displayedTopics = json_decode($displayedTopics);
        if (count($displayedTopics) < 1) {
            return $data;
        }

        $extbaseSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'Typo3Forum');
        $templateRootPaths = $extbaseSettings['view']['templateRootPaths'];

        /* @var StandaloneView $standaloneView */
        $standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
        $standaloneView->setControllerContext($this->controllerContext);
        $standaloneView->setTemplateRootPaths($templateRootPaths);
        $standaloneView->getRenderingContext()->setControllerName('Ajax');
        $standaloneView->setTemplate('topicListMenu');
        $standaloneView->setFormat('html');

        $topicIcons = $this->topicRepository->findByUids($displayedTopics);
        $counter = 0;
        foreach ($topicIcons as $topic) {
            $standaloneView->assign('topic', $topic);
            $data[$counter]['uid'] = $topic->getUid();
            $data[$counter]['replyCount'] = $topic->getReplyCount();
            $data[$counter]['topicListMenu'] = $standaloneView->render();
            $counter++;
        }
        return $data;
    }

    /**
     * @param string $topicIcons
     * @return array
     */
    private function _getTopicIcons($topicIcons)
    {
        $data = [];
        $topicIcons = json_decode($topicIcons);
        if (count($topicIcons) < 1) {
            return $data;
        }
        $this->request->setFormat('html');
        $topicIcons = $this->topicRepository->findByUids($topicIcons);
        $counter = 0;
        foreach ($topicIcons as $topic) {
            $this->view->assign('topic', $topic);
            $data[$counter]['html'] = $this->view->render('topicIcon');
            $data[$counter]['uid'] = $topic->getUid();
            $counter++;
        }
        $this->request->setFormat('json');
        return $data;
    }

    /**
     * @param string $forumIcons
     * @return array
     */
    private function _getForumIcons($forumIcons)
    {
        $data = [];
        $forumIcons = json_decode($forumIcons);
        if (count($forumIcons) < 1) {
            return $data;
        }
        $this->request->setFormat('html');
        $forumIcons = $this->forumRepository->findByUids($forumIcons);
        $counter = 0;
        foreach ($forumIcons as $forum) {
            $this->view->assign('forum', $forum);
            $data[$counter]['html'] = $this->view->render('forumIcon');
            $data[$counter]['uid'] = $forum->getUid();
            $counter++;
        }
        $this->request->setFormat('json');
        return $data;
    }

    /**
     * @param string $postSummarys
     * @return array
     */
    private function _getPostSummarys($postSummarys)
    {
        $postSummarys = json_decode($postSummarys);
        $data = [];
        $counter = 0;
        $this->request->setFormat('html');
        foreach ($postSummarys as $summary) {
            $post = false;
            switch ($summary->type) {
                case 'lastForumPost':
                    $forum = $this->forumRepository->findByUid($summary->uid);
                    /* @var Post */
                    $post = $forum->getLastPost();
                    break;
                case 'lastTopicPost':
                    $topic = $this->topicRepository->findByUid($summary->uid);
                    /* @var Post */
                    $post = $topic->getLastPost();
                    break;
            }
            if ($post) {
                $data[$counter] = $summary;
                $this->view->assign('post', $post)
                    ->assign('hiddenImage', $summary->hiddenimage);
                $data[$counter]->html = $this->view->render('postSummary');
                $counter++;
            }
        }
        $this->request->setFormat('json');
        return $data;
    }

    /**
     * @param array $displayedUser
     * @return array
     */
    private function _getOnlineUser($displayedUser)
    {
        // OnlineUser
        $displayedUser = json_decode($displayedUser);
        $onlineUsers = $this->frontendUserRepository->findByFilter(null, null, true, $displayedUser);
        // write online user
        foreach ($onlineUsers as $onlineUser) {
            $output[] = $onlineUser->getUid();
        }
        if (!empty($output)) {
            return $output;
        }
    }

    /**
     * previewAction.
     */
    public function previewAction()
    {
        $text = '';
        if (($this->request->hasArgument('text'))) {
            $text = $this->request->getArgument('text');
        }

        $this->view->assign('text', $text);
    }
}
