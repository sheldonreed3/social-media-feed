<?php

namespace Drupal\social_feeds_data\Import;

use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;

class SocialFeedsDataImport
{
    protected $config, $fb_data = array(), $tw_data = array(), $inst_data = array();

    function __construct()
    {
        $this->config = \Drupal::config('asuo_social_feeds_api.settings');

        if ($this->config) {
            // Pull in the social data when constructed.
            $this->fb_data = $this->set_fb_data();
            $this->tw_data = $this->set_tw_data();
            $this->inst_data = $this->set_inst_data();
        }
    }

    /**
     * Return a merged array.
     *
     * @return array
     */
    public function get_aggregated_data()
    {
        $merged_array = array_merge($this->tw_data, $this->fb_data, $this->inst_data);
        return $this->sort_aggregated_data($merged_array);
//        return $merged_array;
    }

    public function sort_aggregated_data($data) {
        usort($data, function($a, $b) {
            return ($a->timestamp < $b->timestamp) ? -1 : 1;
        });
        return $data;
    }

    /**
     * Return an indexed, by type, array of social feed data.
     *
     * @return array
     */
    public function get_indexed_data()
    {
        $indexed = array();
        if (!empty($this->fb_data)) {
            $indexed['facebook'] = $this->fb_data;
        }
        if (!empty($this->tw_data)) {
            $indexed['twitter'] = $this->tw_data;
        }
        if (!empty($this->inst_data)) {
            $indexed['instagram'] = $this->inst_data;
        }
        return $indexed;
    }

    /**
     * Public function to access social data after Social Feeds has added some
     * elements.
     *
     * @return array
     */
    public function get_feed_data($type)
    {
        switch (strtolower($type)) {
            case 'twitter':
                return $this->tw_data;
                break;
            case 'facebook':
                return $this->fb_data;
                break;
            case 'instagram':
                return $this->inst_data;
                break;
            default:
                return [];
        }
    }

    /**
     * Standardize Twitter data.
     *
     * @return array
     */
    protected function set_tw_data()
    {
        $data = [];
        $tw = $this->get_twitter_data();
        if ($tw) {
            foreach ($tw as $tweet) {
                if (isset($tweet['extended_entities'])) {
                    // Save image url and post url without the protocol
                    $tweet['image_url'] = str_ireplace(array(
                        'http:',
                        'https:'
                    ), '', $tweet['extended_entities']['media'][0]['media_url']);
                    $tweet['post_url'] = '//' . $tweet['extended_entities']['media'][0]['display_url'];
                    // We need to add type of feed and created timestamp to the post data.
                    $tweet['timestamp'] = strtotime($tweet['created_at']);
                    $tweet['type'] = 'twitter';
                    $object = (object) [];
                    foreach ($tweet as $key => $value)
                    {
                        $object->$key = $value;
                    }
                    $data[] = $object;
                }
            }
        }
        return $data;
    }

    /**
     * Standardize Facebook data.
     *
     * @return array
     */
    protected function set_fb_data()
    {
        $data = array();
        $fb = $this->get_facebook_data();

        if ($fb) {
            $fields = $this->config->get('asuo_social_feeds_fb_fields');
            $fields = $fields ? $fields : ['attachments', 'created_time'];

            foreach ($fb as $post) {
                // Make it so the attachments aren't buried.
                $post->attachments = $post->attachments->data[0];

                // Let's move all pertinent field information to the same level.
                foreach ($fields as $field) {
                    if (is_array($post->{$field})) {
                        foreach ($post->{$field} as $index => $item) {
                            $post->{$index} = $item;
                        }
                        unset($post->{$index});
                    }
                }

                if (isset($post->attachments->description)) {
                    $post->text = $post->attachments->description;
                }
                elseif (isset($post->attachments->title)) {
                    $post->text = $post->attachments->title;
                }
                else {
                    $post->text = 'ASUOnline';
                }

                // Save image url and post url without the protocol
                // We need to grab the first image if there are multiple images
                // associated with this post.
                $post->image_url = isset($post->attachments->subattachments) ? $post->attachments->subattachments->data[0]->media->image->src : $post->attachments->media->image->src;
                $post->image_url = str_ireplace(array('http:', 'https:'), '', $post->image_url);
                $post->post_url = str_ireplace(array('http:', 'https:'), '', $post->attachments->target->url);

                // We need to add type of feed and created timestamp to the post data.
                $post->timestamp = strtotime($post->created_time);
                $post->type = 'facebook';
                $data[] = $post;
            }
        }
        return $data;
    }

    /**
     * Standardize Instagram data.
     *
     * @return array
     */
    protected function set_inst_data()
    {
        $data = array();
        $img_type = $this->config->get('asuo_social_feeds_inst_image_type');
        $img_type = $img_type ? $img_type : 'low_resolution';
        $inst = $this->get_instagram_data();
        if ($inst) {
            foreach ($inst as $post) {
                // Save image url and post url without the protocol
                $post['image_url'] = str_ireplace(array('http:', 'https:'), '', $post['images'][$img_type]['url']);
                $post['post_url'] = str_ireplace(array('http:', 'https:'), '', $post['link']);
                // We need to add type of feed and created timestamp to the post data.
                $post['timestamp'] = $post['created_time'];
                $post['type'] = 'instagram';
                $post['text'] = $post['caption']['text'];
                $object = (object) [];
                foreach ($post as $key => $value)
                {
                    $object->$key = $value;
                }
                $data[] = $object;
//                $data[] = (object) $post;
            }
        }
        return $data;
    }

    /**
     * Public function to return unedited Instagram data.
     *
     * @return array|bool|mixed
     */
    public function get_instagram_data()
    {
        $username = Html::escape($this->config->get('asuo_social_feeds_instagram_username'));

        if (!$username) {
            return FALSE;
        } else {
            $access_token = Html::escape($this->config->get('asuo_social_feeds_instagram_access_token'));
            $count = Html::escape($this->config->get('asuo_social_feeds_instagram_count'));
            $count = $count ? $count : 8;

            try {
                // Try the 'correct' way first with access_token.
                $url = "https://api.instagram.com/v1/users/self/media/recent/?access_token=$access_token&count=$count";
                $connection = \Drupal::httpClient();
                $request = $connection->get($url);
                $data = Json::decode($request->getBody()->getContents())['data'];
            } catch (Exception $e) {
                if ($e->getCode() === 400) {

                    $url = "https://www.instagram.com/$username/media/?size=t";

                    $connection = \Drupal::httpClient();
                    $request = $connection->get($url);
                    $data = json_decode($request->getBody());
                    $data = array_slice($data['items'], 0, $count);

                    \Drupal::logger('asuo_social_feeds')->error('The instagram access_token needs to be updated.');
//                    $client_id = Html::escape($this->config->get('asuo_social_feeds_instagram_client_id'));
//                    $token_url = "https://www.instagram.com/oauth/authorize/";
//                    $qry_str="client_id=$client_id&redirect_uri=http://newsroom&response_type=code";
//
//                    $url = $token_url . '?' . $qry_str;
//
//                    $client = new \GuzzleHttp\Client(['allow_redirects' => ['track_redirects' => true]]);
//                    $r = $client->get($url, [
//                        'on_stats' => function (\GuzzleHttp\TransferStats $stats) use (&$url) {
//                            $url = $stats->getEffectiveUri();
//                        }
//                    ])->getBody()->getContents();
//
//                    ksm($r);
                }
            }

            return $data;
        }
    }

    /**
     * Public function to retrieve unedited Facebook data.
     *
     * @return bool|mixed
     */
    public function get_facebook_data()
    {
        // Let's grab the site name from the config form.
        $sitename = Html::escape($this->config->get('asuo_social_feeds_fb_sitename'));

        // If there is no site name we do not need to import this type.
        if (!$sitename) {
            return FALSE;
        } else {
            // Let's grab the rest of the settings from the config form.
            $app_id = Html::escape($this->config->get('asuo_social_feeds_fb_app_id'));
            $secret_key = Html::escape($this->config->get('asuo_social_feeds_fb_secret_key'));
            $count = Html::escape($this->config->get('asuo_social_feeds_fb_count'));
            $count = $count ? $count : 8;
            $fields = Html::escape($this->config->get('asuo_social_feeds_fb_fields'));
            $fields = $fields ? $fields : 'attachments,created_time';

            // Grab facebook data.
            $url = "https://graph.facebook.com/$sitename/posts?&limit=$count&fields=$fields&access_token=$app_id|$secret_key";
//            $request = drupal_http_request($url);
//            $data = json_decode($request->data);
            $connection = \Drupal::httpClient();
            $request = $connection->get($url);
            $data = json_decode($request->getBody())->data;

//            $data =  $data['data'];
            return $data;
        }
    }

    /**
     * Public function to retrieve unedited Twitter data.
     *
     * @return bool|mixed
     */
    public function get_twitter_data()
    {
        // Let's grab the username from the config form.
        $twitter_username = Html::escape($this->config->get('asuo_social_feeds_twitter_username'));

        if (!$twitter_username) {
            return FALSE;
        } else {
            // Let's grab the rest of the settings.
            $twitter_consumer_key = $this->config->get('asuo_social_feeds_twitter_app_id');
            $twitter_consumer_secrete = $this->config->get('asuo_social_feeds_twitter_secret_key');
            $data_count = $this->config->get('asuo_social_feeds_twitter_count');
            $data_count = $data_count ? $data_count : 8;
            $only_images = $this->config->get('asuo_social_feeds_twitter_only_images');
            $only_images = $only_images ? $only_images : 1;

            // Import code below copied from socialfeed module https://www.drupal.org/project/socialfeed.
            // Auth Parameters.
            $api_key = urlencode($twitter_consumer_key);
            $api_secret = urlencode($twitter_consumer_secrete);
            $auth_url = 'https://api.twitter.com/oauth2/token';

            // What we want?
            $data_username = $twitter_username;
            $data_url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

            // Get API Access Token.
            $api_credentials = base64_encode($api_key . ':' . $api_secret);
            $auth_headers = 'Authorization: Basic ' . $api_credentials . "\r\n" . 'Content-Type: application/x-www-form-urlencoded;charset=UTF-8' . "\r\n";
            $auth_context = stream_context_create(
                array(
                    'http' => array(
                        'header' => $auth_headers,
                        'method' => 'POST',
                        'content' => http_build_query(
                            array(
                                'grant_type' => 'client_credentials',
                            )
                        ),
                    ),
                )
            );

            $auth_response = json_decode(file_get_contents($auth_url, 0, $auth_context), TRUE);
            $auth_token = $auth_response['access_token'];

            // Get Tweets.
            $data_context = stream_context_create(
                array(
                    'http' => array(
                        'header' => 'Authorization: Bearer ' . $auth_token . "\r\n",
                    ),
                )
            );
            $extra = $only_images ? '&filter=images&include_entities=true' : '';
            $data = json_decode(file_get_contents($data_url . '?count=' . $data_count . '&screen_name=' . urlencode($data_username) . $extra, 0, $data_context), TRUE);
            // End socialfeed code.

            return $data;
        }
    }
}