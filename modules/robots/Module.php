<?php

    namespace nox\modules\robots;

    use nox\helpers\StringHelper;
    use yii\base\InvalidConfigException;

    /**
     * Class Module
     *
     * @package nox\modules\robots
     */
    class Module extends \yii\base\Module
    {
        /**
         * @var string
         */
        public $controllerNamespace = 'nox\modules\robots\controllers';

        /**
         * @var string
         */
        public $viewPath = '@nox-it/robots/views';

        /**
         * @var string
         */
        public $layout = false;

        /**
         * @var string
         */
        public $defaultRoute = 'default/index';

        /**
         * @var array
         */
        public $settings = [];

        /**
         * @var array
         */
        protected $defaultSettings = [
            'disallowAllRobots' => false,
            'allowAllRobots'    => false,
            'useSitemap'        => true,
            'sitemapFile'       => '/sitemap.xml',
            'robots'            => [],
            'allowRules'        => [],
            'disallowRules'     => []
        ];

        /**
         * @var bool
         */
        protected $allowAllRobots = false;

        /**
         * @var bool
         */
        protected $disallowAllRobots = false;

        /**
         * @var bool
         */
        protected $useSitemap = true;

        /**
         * @var string
         */
        protected $sitemapFile = 'sitemap.xml';

        /**
         * @var array
         */
        protected $allowRules = [];

        /**
         * @var array
         */
        protected $disallowRules = [];

        /**
         * @var array
         */
        protected $robots = [
            'all'                  => '*',
            'googlebot'            => 'Googlebot',
            'googlebot-mobile'     => 'Googlebot-Mobile',
            'googlebot-image'      => 'Googlebot-Image',
            'mediapartners-google' => 'Mediapartners-Google',
            'adsbot-google'        => 'Adsbot-Google',
            'slurp'                => 'Slurp',
            'msnbot'               => 'msnbot',
            'msnbot-media'         => 'msnbot-media',
            'teoma'                => 'Teoma'
        ];

        #region Initialization
        /**
         * @inheritdoc
         */
        public function init()
        {
            parent::init();

            \Yii::setAlias('@nox-it/robots', __DIR__);

            $this->verifyComponentRequirements();
        }

        /**
         * @return bool
         *
         * @throws InvalidConfigException
         */
        protected function verifyComponentRequirements()
        {
            if (!is_array($this->settings)) {
                throw new InvalidConfigException('');
            }

            if (!isset($this->settings['disallowAllRobots'])) {
                $this->disallowAllRobots = $this->defaultSettings['disallowAllRobots'];
            } else {
                $this->disallowAllRobots = (bool)$this->settings['disallowAllRobots'];
            }

            if (!isset($this->settings['allowAllRobots'])) {
                $this->allowAllRobots = $this->defaultSettings['allowAllRobots'];
            } else {
                $this->allowAllRobots = (bool)$this->settings['allowAllRobots'];
            }

            if ($this->settings['allowAllRobots']) {
                $this->disallowAllRobots = false;
            }

            if (!isset($this->settings['useSitemap'])) {
                $this->useSitemap = $this->defaultSettings['useSitemap'];
            } else {
                $this->useSitemap = (bool)$this->settings['useSitemap'];
            }

            if (!isset($this->settings['sitemapFile'])) {
                $this->sitemapFile = $this->defaultSettings['sitemapFile'];
            } else {
                $this->sitemapFile = (string)$this->settings['sitemapFile'];
            }

            if (is_array($this->settings['robots']) && count($this->settings['robots']) > 0) {
                foreach ($this->settings['robots'] as $robot) {
                    $this->addRobot($robot);
                }
            }

            if (!$this->allowAllRobots && !$this->disallowAllRobots) {
                if (is_array($this->settings['allowRules']) && count($this->settings['allowRules']) > 0) {
                    foreach ($this->settings['allowRules'] as $robot => $rules) {
                        $robotId = $this->getRobotId($robot);

                        if (!isset($this->allowRules[$robotId]) || !is_array($this->allowRules[$robotId])) {
                            $this->allowRules[$robotId] = [];
                        }

                        if (is_array($rules) && count($rules) > 0) {
                            foreach ($rules as $rule) {
                                $this->allowRules[$robotId][] = $rule;
                            }
                        }
                    }
                }

                if (is_array($this->settings['disallowRules']) && count($this->settings['disallowRules']) > 0) {
                    foreach ($this->settings['disallowRules'] as $robot => $rules) {
                        $robotId = $this->getRobotId($robot);

                        if (!isset($this->disallowRules[$robotId]) || !is_array($this->disallowRules[$robotId])) {
                            $this->disallowRules[$robotId] = [];
                        }

                        if (is_array($rules) && count($rules) > 0) {
                            foreach ($rules as $rule) {
                                $this->disallowRules[$robotId][] = $rule;
                            }
                        }
                    }
                }
            }

            return false;
        }
        #endregion

        #region Getters and Setters
        /**
         * @return array
         */
        public function getRobots()
        {
            return $this->robots;
        }

        /**
         * @param string $robot
         *
         * @return static
         */
        public function addRobot($robot)
        {
            $robotId = $this->getRobotId($robot);

            if (!isset($this->robots[$robotId])) {
                $this->robots[$robotId] = $robot;
            }

            return $this;
        }

        /**
         * @return array
         */
        public function getAllowRules()
        {
            return $this->allowRules;
        }

        /**
         * @param string $path
         * @param string $robot
         *
         * @return bool
         */
        public function addAllowRule($path, $robot)
        {
            $robotId = $this->getRobotId($robot);

            if ($this->robotExists($robot)) {
                if (!isset($this->allowRules[$robotId]) || !is_array($this->allowRules[$robotId])) {
                    $this->allowRules[$robotId] = [];
                }

                $this->allowRules[$robotId][] = (string)$path;

                return true;
            }

            return false;
        }

        /**
         * @return array
         */
        public function getDisallowRules()
        {
            return $this->disallowRules;
        }

        /**
         * @param string $path
         * @param string $robot
         *
         * @return bool
         */
        public function addDisallowRule($path, $robot)
        {
            $robotId = $this->getRobotId($robot);

            if ($this->robotExists($robot)) {
                if (!isset($this->disallowRules[$robotId]) || !is_array($this->disallowRules[$robotId])) {
                    $this->disallowRules[$robotId] = [];
                }

                $this->disallowRules[$robotId][] = (string)$path;

                return true;
            }

            return false;
        }

        /**
         * @param string $robot
         *
         * @return string
         */
        public function getRobotId($robot)
        {
            if ($robot == '*') {
                $robot = 'all';
            }

            $robotId = StringHelper::asSlug($robot);

            return $robotId;
        }

        /**
         * @param string $robotId
         *
         * @return string
         */
        public function getRobotName($robotId)
        {
            if ($robotId == '*') {
                $robotId = 'all';
            }

            if (isset($this->robots[$robotId])) {
                return $this->robots[$robotId];
            }

            return $this->robots['all'];
        }

        /**
         * @return array
         */
        public function getRobotsData()
        {
            return [
                'disallowAllRobots' => $this->disallowAllRobots,
                'allowAllRobots'    => $this->allowAllRobots,
                'useSitemap'        => $this->useSitemap,
                'sitemapFile'       => $this->sitemapFile,
                'allowRules'        => $this->allowRules,
                'disallowRules'     => $this->disallowRules,
                'robotsModule'      => $this
            ];
        }
        #endregion

        #region Verifications
        /**
         * @param string $robot
         * @param bool   $create
         *
         * @return bool
         */
        public function robotExists($robot, $create = true)
        {
            $robotId = $this->getRobotId($robot);

            if (!isset($this->robots[$robotId])) {
                if ((bool)$create) {
                    $this->addRobot($robot);

                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }
        #endregion
    }
