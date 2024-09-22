<?php
/**
 *
 * Comma statistics. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024, Anișor, https://phpbb.ro
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anix\commastats\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Comma statistics Event listener.
 */
class main_listener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'							=> 'load_language_on_setup',
			'core.index_modify_page_title'				=> 'index_modify_page_title',
			'core.display_forums_modify_template_vars'	=> 'display_forums_modify_template_vars',
			'core.viewforum_modify_topicrow'			=> 'viewforum_modify_topicrow',
			'core.viewtopic_cache_user_data'			=> 'viewtopic_cache_user_data',
			'core.search_modify_tpl_ary'				=> 'search_modify_tpl_ary',
			'core.memberlist_prepare_profile_data'		=> 'memberlist_prepare_profile_data'
		];
	}

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\content_visibility */
	protected $phpbb_content_visibility;

	/**
	 * Constructor
	 *
	 * @param \phpbb\language\language	$language	Language object
	 * @param \phpbb\template\template    $template        Template object
	 * @param \phpbb\config\config     $config   Config object
	 * 
	 */

	public function __construct(
		\phpbb\language\language $language, 
		\phpbb\template\template $template,
		\phpbb\config\config $config,
		\phpbb\content_visibility $phpbb_content_visibility
	)
	{
		$this->language = $language;
		$this->template = $template;
		$this->config = $config;
		$this->phpbb_content_visibility = $phpbb_content_visibility;
	}

	/**
	 * Load common language files during user setup
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'anix/commastats',
			'lang_set' => 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	//Index statistics
	public function index_modify_page_title()
	{
		//Rewrite default templates.
        $this->template->assign_vars([
            'TOTAL_POSTS' 		=> $this->language->lang('TOTAL_POSTS_COUNT', number_format($this->config['num_posts'])),
			'TOTAL_TOPICS' 		=> $this->language->lang('TOTAL_TOPICS', number_format($this->config['num_topics'])),
			'TOTAL_USERS' 		=> $this->language->lang('TOTAL_USERS', number_format($this->config['num_users'])),
        ]);
	}

	//Forumlist
	public function display_forums_modify_template_vars($event) 
	{
		$row = $event['row'];
		$forum_row = $event['forum_row'];

		$l_post_click_count = ($row['forum_type'] == FORUM_LINK) ? 'CLICKS' : 'POSTS';
		$post_click_count = ($row['forum_type'] != FORUM_LINK || $row['forum_flags'] & FORUM_FLAG_LINK_TRACK) ? number_format($row['forum_posts']) : '';

		//Rewrite default templates.
		$forum_row['TOPICS'] 				= number_format($row['forum_topics']);
		$forum_row['POSTS'] 				= number_format($row['forum_posts']);
		$forum_row[$l_post_click_count] 	= $post_click_count;

		//Re-assign modified forum row to the event
		$event['forum_row'] = $forum_row;
	}

	//Viewforum
	public function viewforum_modify_topicrow($event) 
	{
		$row = $event['row'];
		$topic_row = $event['topic_row'];

		$replies = $this->phpbb_content_visibility->get_count('topic_posts', $row, $row['forum_id']) - 1;
		
		// Correction for case of unapproved topic visible to poster
		if ($replies < 0)
		{
			$replies = 0;
		}

		//Rewrite default templates.
		$topic_row['VIEWS'] 	= number_format($row['topic_views']);
		$topic_row['REPLIES'] 	= number_format($replies);

		//Re-assign modified topic row to the event
		$event['topic_row'] = $topic_row;
	}

	//Viewtopic
	public function viewtopic_cache_user_data($event)
	{
		//load template array data
		$user_cache_data = $event['user_cache_data'];
		$row = $event['row'];

		$user_cache_data['posts'] = number_format($row['user_posts']);

		//reassign the modified template data back to the event
		$event['user_cache_data'] = $user_cache_data;
	}

	//Search
	public function search_modify_tpl_ary($event)
	{
		$row = $event['row'];
		$tpl_ary = $event['tpl_ary'];

		$replies = $this->phpbb_content_visibility->get_count('topic_posts', $row, $row['forum_id']) - 1;

		//Rewrite default templates.
		$tpl_ary['TOPIC_VIEWS'] 	= number_format($row['topic_views']);
		$tpl_ary['TOPIC_REPLIES'] 	= number_format($replies);

		//Re-assign modified tpl array row to the event
		$event['tpl_ary'] = $tpl_ary;
	}

	//Memberlist profile
	public function memberlist_prepare_profile_data($event)
	{
		//load template array data
        $template_data = $event['template_data'];
		$data = $event['data'];

		$template_data['POSTS'] = (number_format($data['user_posts'])) ? number_format($data['user_posts']) : 0;

        //reassign the modified template data back to the event
        $event['template_data'] = $template_data;
	}
}
