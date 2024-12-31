<?php

if (!class_exists('Bluesky')) {
    class Bluesky
    {
        private $apiBaseUrl;
        private $accessToken;

        public function __construct($apiBaseUrl = 'https://bsky.social/xrpc/')
        {
            $this->apiBaseUrl = $apiBaseUrl;
            add_action('admin_menu', [$this, 'add_bluesky_config_page']);
            add_action('admin_init', [$this, 'register_bluesky_settings']);
            add_shortcode('bluesky_feed', [$this, 'bluesky_feed_shortcode']);
        }

        // Add settings page to the admin menu
        public function add_bluesky_config_page()
        {
            add_menu_page(
                'Bluesky Config',
                'Bluesky Config',
                'manage_options',
                'bluesky-config',
                [$this, 'bluesky_config_page_html'],
                'dashicons-cloud',
                90
            );
        }

        // Register settings
        public function register_bluesky_settings()
        {
            register_setting('bluesky_settings', 'bluesky_username');
            register_setting('bluesky_settings', 'bluesky_password');
        }

        // Render the settings page HTML
        public function bluesky_config_page_html()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            if (isset($_POST['bluesky_clear_cache'])) {
                $this->clearCache();
                echo '<div class="updated"><p>Cache cleared successfully!</p></div>';
            }

            ?>
            <div class="wrap">
                <h1>Bluesky Config</h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('bluesky_settings');
                    do_settings_sections('bluesky_settings');
                    ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><label for="bluesky_username">Username</label></th>
                            <td><input type="text" name="bluesky_username" id="bluesky_username"
                                       value="<?php echo esc_attr(get_option('bluesky_username', '')); ?>"
                                       class="regular-text"></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="bluesky_password">Password</label></th>
                            <td><input type="password" name="bluesky_password" id="bluesky_password"
                                       value="<?php echo esc_attr(get_option('bluesky_password', '')); ?>"
                                       class="regular-text"></td>
                        </tr>
                    </table>
                    <?php submit_button('Save Account'); ?>
                </form>

                <form method="post" action="">
                    <?php wp_nonce_field('bluesky_clear_cache_action', 'bluesky_clear_cache_nonce'); ?>
                    <input type="hidden" name="bluesky_clear_cache" value="1">
                    <button type="submit" class="button button-secondary">Force Delete Cache</button>
                </form>
            </div>
            <?php
        }

        // Shortcode for displaying feed
        public function bluesky_feed_shortcode($atts)
        {
            $atts = shortcode_atts(['count' => 1], $atts, 'bluesky_feed');
            $count = (int)$atts['count'];

            try {
                $key = 'primary_feed';
                $feed = $this->getCache($key);

                if (!$feed) {
                    $this->authenticate();
                    $feed = $this->fetchFeed();
                    $this->setCache($key, $feed);
                }

                $limitedFeed = array_slice($feed['feed'], 0, $count);

                return $this->displayFeed($limitedFeed);

            } catch (Exception $e) {
                return "<p>Error: " . esc_html($e->getMessage()) . "</p>";
            }
        }

        private function clearCache()
        {
            delete_transient('bluesky_cache_primary_feed');
        }

        private function getCache($key)
        {
            $cacheKey = 'bluesky_cache_' . $key;
            $cachedData = get_transient($cacheKey);
            if ($cachedData && isset($cachedData['expiration']) && time() < $cachedData['expiration']) {
                return $cachedData['data'];
            }
            return false;
        }

        private function setCache($key, $data)
        {
            $cacheKey = 'bluesky_cache_' . $key;
            $cacheData = [
                'data' => $data,
                'expiration' => strtotime('tomorrow'),
            ];
            set_transient($cacheKey, $cacheData, DAY_IN_SECONDS);
        }

        private function authenticate()
        {
            $url = $this->apiBaseUrl . 'com.atproto.server.createSession';

            $username = get_option('bluesky_username', '');
            $password = get_option('bluesky_password', '');

            if (!$username || !$password) {
                throw new Exception("No account authentication found");
            }

            $data = [
                'identifier' => $username,
                'password' => $password,
            ];

            $options = [
                'http' => [
                    'header' => "Content-Type: application/json\r\n",
                    'method' => 'POST',
                    'content' => json_encode($data),
                ],
            ];

            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);

            if ($response === FALSE) {
                throw new Exception("Error authenticating account with Bluesky");
            }

            $response_data = json_decode($response, true);
            $this->accessToken = $response_data['accessJwt'] ?? null;

            if (!$this->accessToken) {
                throw new Exception("Failed to retrieve access token");
            }
        }

        private function fetchFeed()
        {
            $url = $this->apiBaseUrl . 'app.bsky.feed.getAuthorFeed';
            $handle = get_option('bluesky_username', '');

            if (!$handle) {
                throw new Exception("No handle found for account.");
            }

            $url .= '?' . http_build_query(['actor' => $handle]);

            $options = [
                'http' => [
                    'header' => "Authorization: Bearer {$this->accessToken}\r\n",
                    'method' => 'GET',
                ],
            ];

            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            if ($response === FALSE) {
                throw new Exception("Error fetching Bluesky feed");
            }

            $feed = json_decode($response, true);

            return empty($feed['feed']) ? ['feed' => []] : $feed;
        }

        private function displayFeed($feed)
        {
            if (empty($feed)) {
                return "<p>No feed data available.</p>";
            }

            $handle = get_option('bluesky_username', '');

            ob_start();
            echo "<div class='bluesky-feed'>
                    <a href='https://bsky.app/profile/{$handle}' class='bluesky-account'>Posts from @{$handle}</a>";

            foreach ($feed as $post) {
                $author = esc_html($post['post']['author']['handle'] ?? 'Unknown Author');
                $username = esc_html($post['post']['author']['displayName'] ?? 'Anonymous');
                $avatar = esc_url($post['post']['author']['avatar'] ?? 'https://via.placeholder.com/48');
                $content = esc_html($post['post']['record']['text']) ?? '';
                $relativeTime = $this->relativeTime($post['post']['author']['createdAt']);

                echo "<div class='bluesky-post'>
                        <div class='bluesky-header'>
                            <div class='bluesky-avatar'><img src='{$avatar}' alt='Avatar of {$author}' /></div>
                            <div class='bluesky-user-details'>
                                <span class='bluesky-username'>{$username}</span>
                                <span class='bluesky-handle'>@{$author}</span>
                                <span class='bluesky-time'>· {$relativeTime}</span>
                            </div>
                        </div>
                        <div class='bluesky-content'>{$content}</div>
                    </div>";
            }

            echo "<a href='https://bsky.app/profile/{$handle}' class='bluesky-button'>View on BlueSky</a></div>";

            return ob_get_clean();
        }

        private function relativeTime($datetime)
        {
            $timestamp = strtotime($datetime);
            $diff = time() - $timestamp;
            if ($diff < 60) {
                return $diff . 's';
            } elseif ($diff < 3600) {
                return floor($diff / 60) . 'm';
            } elseif ($diff < 86400) {
                return floor($diff / 3600) . 'h';
            } else {
                return floor($diff / 86400) . 'd';
            }
        }
    }
}
