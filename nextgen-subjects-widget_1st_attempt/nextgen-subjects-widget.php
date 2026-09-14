<?php
/**
 * Plugin Name: NextGen Subjects Widget
 * Description: Native WordPress widget and shortcode for rendering a configurable list of tutoring subjects.
 * Version: 1.0.0
 * Author: NextGen Tutors
 * Text Domain: nextgen-subjects-widget
 */

defined('ABSPATH') || exit;

final class NextGen_Subjects_Widget_Plugin
{
    private const VERSION = '1.0.0';

    public static function init(): void
    {
        add_action('widgets_init', [self::class, 'register_widget']);
        add_action('wp_enqueue_scripts', [self::class, 'register_assets']);
        add_shortcode('nextgen_subjects', [self::class, 'shortcode']);
    }

    public static function register_widget(): void
    {
        register_widget(NextGen_Subjects_Widget::class);
    }

    public static function register_assets(): void
    {
        wp_register_style(
            'nextgen-subjects-widget',
            plugin_dir_url(__FILE__) . 'assets/css/nextgen-subjects-widget.css',
            [],
            self::VERSION
        );

        wp_register_script(
            'nextgen-subjects-widget',
            plugin_dir_url(__FILE__) . 'assets/js/nextgen-subjects-widget.js',
            [],
            self::VERSION,
            true
        );
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function parse_subjects(string $input): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $input) ?: [];
        $subjects = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line, 3));
            $name = sanitize_text_field($parts[0] ?? '');

            if ($name === '') {
                continue;
            }

            $description = sanitize_text_field(
                $parts[1] ?? sprintf(
                    __('Expert tutoring and personalised support in %s.', 'nextgen-subjects-widget'),
                    $name
                )
            );

            $icon = sanitize_key($parts[2] ?? self::guess_icon($name));

            $subjects[] = [
                'name'        => $name,
                'description' => $description,
                'icon'        => $icon,
            ];
        }

        return $subjects;
    }

    private static function guess_icon(string $subject): string
    {
        $subject = strtolower($subject);

        $map = [
            'math'        => 'calculator',
            'mathematics' => 'calculator',
            'english'     => 'book',
            'afrikaans'   => 'message',
            'physics'     => 'atom',
            'science'     => 'flask',
            'chemistry'   => 'flask',
            'biology'     => 'leaf',
            'geography'   => 'globe',
            'history'     => 'landmark',
            'accounting'  => 'chart',
            'economics'   => 'chart',
            'coding'      => 'code',
            'computer'    => 'code',
            'technology'  => 'cpu',
        ];

        foreach ($map as $keyword => $icon) {
            if (str_contains($subject, $keyword)) {
                return $icon;
            }
        }

        return 'book';
    }

    /**
     * @param array<string, mixed> $atts
     */
    public static function shortcode(array $atts = []): string
    {
        $atts = shortcode_atts(
            [
                'title'       => 'Explore Our Subjects',
                'subtitle'    => 'Find expert tutors across the subjects you need.',
                'subjects'    => '',
                'columns'     => '4',
                'show_search' => 'yes',
            ],
            $atts,
            'nextgen_subjects'
        );

        $subjectsInput = str_replace(',', PHP_EOL, (string) $atts['subjects']);
        $subjects = self::parse_subjects($subjectsInput);

        return self::render(
            $subjects,
            [
                'title'       => sanitize_text_field((string) $atts['title']),
                'subtitle'    => sanitize_text_field((string) $atts['subtitle']),
                'columns'     => self::sanitize_columns($atts['columns']),
                'show_search' => $atts['show_search'] === 'yes',
            ]
        );
    }

    /**
     * @param mixed $columns
     */
    public static function sanitize_columns($columns): int
    {
        $columns = absint($columns);

        return in_array($columns, [2, 3, 4, 5, 6], true)
            ? $columns
            : 4;
    }

    /**
     * @param array<int, array<string, string>> $subjects
     * @param array<string, mixed>              $settings
     */
    public static function render(array $subjects, array $settings): string
    {
        if (!$subjects) {
            return '';
        }

        wp_enqueue_style('nextgen-subjects-widget');
        wp_enqueue_script('nextgen-subjects-widget');

        $instanceId = wp_unique_id('ng-subjects-');

        $title = sanitize_text_field(
            (string) ($settings['title'] ?? 'Explore Our Subjects')
        );

        $subtitle = sanitize_text_field(
            (string) ($settings['subtitle'] ?? '')
        );

        $columns = self::sanitize_columns(
            $settings['columns'] ?? 4
        );

        $showSearch = !empty($settings['show_search']);

        ob_start();
        ?>
        <section
            id="<?php echo esc_attr($instanceId); ?>"
            class="ng-subjects"
            data-nextgen-subjects
            style="--ng-subject-columns: <?php echo esc_attr((string) $columns); ?>;"
            aria-labelledby="<?php echo esc_attr($instanceId); ?>-title"
        >
            <div class="ng-subjects__container">

                <header class="ng-subjects__header">

                    <div class="ng-subjects__eyebrow">
                        <span class="ng-subjects__eyebrow-dot" aria-hidden="true"></span>
                        <?php esc_html_e('NextGen Tutors', 'nextgen-subjects-widget'); ?>
                    </div>

                    <?php if ($title !== '') : ?>
                        <h2
                            id="<?php echo esc_attr($instanceId); ?>-title"
                            class="ng-subjects__title"
                        >
                            <?php echo esc_html($title); ?>
                        </h2>
                    <?php endif; ?>

                    <?php if ($subtitle !== '') : ?>
                        <p class="ng-subjects__subtitle">
                            <?php echo esc_html($subtitle); ?>
                        </p>
                    <?php endif; ?>

                </header>

                <?php if ($showSearch) : ?>
                    <div class="ng-subjects__search-container">
                        <label
                            class="screen-reader-text"
                            for="<?php echo esc_attr($instanceId); ?>-search"
                        >
                            <?php esc_html_e('Search subjects', 'nextgen-subjects-widget'); ?>
                        </label>

                        <span class="ng-subjects__search-icon" aria-hidden="true">
                            <?php echo self::icon('search'); ?>
                        </span>

                        <input
                            id="<?php echo esc_attr($instanceId); ?>-search"
                            class="ng-subjects__search"
                            type="search"
                            placeholder="<?php esc_attr_e('Search subjects...', 'nextgen-subjects-widget'); ?>"
                            autocomplete="off"
                            data-subject-search
                        >
                    </div>
                <?php endif; ?>

                <div
                    class="ng-subjects__grid"
                    data-subject-grid
                    role="list"
                >
                    <?php foreach ($subjects as $subject) : ?>
                        <?php
                        $name = sanitize_text_field($subject['name']);
                        $description = sanitize_text_field($subject['description']);
                        $searchValue = strtolower($name . ' ' . $description);
                        ?>

                        <article
                            class="ng-subject-card"
                            data-subject-card
                            data-subject-search-value="<?php echo esc_attr($searchValue); ?>"
                            role="listitem"
                        >
                            <div class="ng-subject-card__glow" aria-hidden="true"></div>

                            <div class="ng-subject-card__icon" aria-hidden="true">
                                <?php echo self::icon($subject['icon']); ?>
                            </div>

                            <div class="ng-subject-card__content">
                                <h3 class="ng-subject-card__title">
                                    <?php echo esc_html($name); ?>
                                </h3>

                                <p class="ng-subject-card__description">
                                    <?php echo esc_html($description); ?>
                                </p>
                            </div>

                            <span class="ng-subject-card__arrow" aria-hidden="true">
                                <?php echo self::icon('arrow'); ?>
                            </span>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div
                    class="ng-subjects__empty"
                    data-subject-empty
                    hidden
                >
                    <div class="ng-subjects__empty-icon" aria-hidden="true">
                        <?php echo self::icon('search'); ?>
                    </div>

                    <strong>
                        <?php esc_html_e('No subjects found', 'nextgen-subjects-widget'); ?>
                    </strong>

                    <span>
                        <?php esc_html_e('Try another search term.', 'nextgen-subjects-widget'); ?>
                    </span>
                </div>

            </div>
        </section>
        <?php

        return (string) ob_get_clean();
    }

    private static function icon(string $icon): string
    {
        $icons = [
            'calculator' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M8 6h8M8 10h2M14 10h2M8 14h2M14 14h2M8 18h2M14 18h2"/></svg>',
            'book' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>',
            'atom' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="1"/><path d="M20.2 7.8c1.9 3.3-1.8 8.5-6.2 11s-10 1.6-11.8-1.7 1.8-8.5 6.2-11 10-1.6 11.8 1.7Z"/><path d="M20.2 16.2c-1.9 3.3-7.4 2.2-11.8-.3s-8.1-7.7-6.2-11S9.6 2.7 14 5.2s8.1 7.7 6.2 11Z"/></svg>',
            'flask' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.7 3h10.6A2 2 0 0 0 19 18l-5-9V3"/><path d="M7.5 15h9"/></svg>',
            'leaf' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 4.4 20 5 20 5s.6 4.5-1.1 10.2A7 7 0 0 1 11 20Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6.94C9.2 13 12 12 16 12"/></svg>',
            'globe' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>',
            'landmark' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-6 9 6M5 10v8M9 10v8M15 10v8M19 10v8M3 21h18M2 18h20"/></svg>',
            'chart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="m7 16 4-5 4 3 5-7"/></svg>',
            'code' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8 9-4 3 4 3M16 9l4 3-4 3M14 5l-4 14"/></svg>',
            'cpu' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"/></svg>',
            'message' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>',
            'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
            'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
        ];

        return $icons[$icon] ?? $icons['book'];
    }
}

final class NextGen_Subjects_Widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'nextgen_subjects_widget',
            __('NextGen Subjects', 'nextgen-subjects-widget'),
            [
                'classname'   => 'nextgen-subjects-widget',
                'description' => __('Displays a responsive, searchable list of subjects.', 'nextgen-subjects-widget'),
            ]
        );
    }

    public function widget($args, $instance): void
    {
        $subjects = NextGen_Subjects_Widget_Plugin::parse_subjects(
            (string) ($instance['subjects'] ?? '')
        );

        $settings = [
            'title'       => sanitize_text_field((string) ($instance['title'] ?? 'Explore Our Subjects')),
            'subtitle'    => sanitize_text_field((string) ($instance['subtitle'] ?? '')),
            'columns'     => NextGen_Subjects_Widget_Plugin::sanitize_columns($instance['columns'] ?? 4),
            'show_search' => !empty($instance['show_search']),
        ];

        echo $args['before_widget'];
        echo NextGen_Subjects_Widget_Plugin::render($subjects, $settings);
        echo $args['after_widget'];
    }

    public function update($newInstance, $oldInstance): array
    {
        return [
            'title'       => sanitize_text_field((string) ($newInstance['title'] ?? '')),
            'subtitle'    => sanitize_text_field((string) ($newInstance['subtitle'] ?? '')),
            'subjects'    => sanitize_textarea_field((string) ($newInstance['subjects'] ?? '')),
            'columns'     => NextGen_Subjects_Widget_Plugin::sanitize_columns($newInstance['columns'] ?? 4),
            'show_search' => !empty($newInstance['show_search']) ? 1 : 0,
        ];
    }

    public function form($instance): void
    {
        $defaults = [
            'title'       => 'Explore Our Subjects',
            'subtitle'    => 'Expert tutors. Personalised learning. Better results.',
            'columns'     => 4,
            'show_search' => 1,
            'subjects'    => implode(
                PHP_EOL,
                [
                    'Mathematics|Master concepts from arithmetic to advanced mathematics.|calculator',
                    'English|Improve language, literature, comprehension and writing skills.|book',
                    'Physical Science|Build confidence in physics and chemistry.|atom',
                    'Life Sciences|Understand biology and the science of living systems.|leaf',
                    'Accounting|Develop strong financial and accounting skills.|chart',
                    'Geography|Explore physical and human geography with expert guidance.|globe',
                    'History|Understand events, people and ideas that shaped our world.|landmark',
                    'Computer Science|Learn coding, computing and digital technology.|code',
                ]
            ),
        ];

        $instance = wp_parse_args($instance, $defaults);
        ?>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'nextgen-subjects-widget'); ?>
            </label>
            <input
                class="widefat"
                id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                type="text"
                value="<?php echo esc_attr((string) $instance['title']); ?>"
            >
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('subtitle')); ?>">
                <?php esc_html_e('Subtitle:', 'nextgen-subjects-widget'); ?>
            </label>
            <input
                class="widefat"
                id="<?php echo esc_attr($this->get_field_id('subtitle')); ?>"
                name="<?php echo esc_attr($this->get_field_name('subtitle')); ?>"
                type="text"
                value="<?php echo esc_attr((string) $instance['subtitle']); ?>"
            >
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('columns')); ?>">
                <?php esc_html_e('Columns:', 'nextgen-subjects-widget'); ?>
            </label>
            <select
                class="widefat"
                id="<?php echo esc_attr($this->get_field_id('columns')); ?>"
                name="<?php echo esc_attr($this->get_field_name('columns')); ?>"
            >
                <?php foreach ([2, 3, 4, 5, 6] as $column) : ?>
                    <option
                        value="<?php echo esc_attr((string) $column); ?>"
                        <?php selected((int) $instance['columns'], $column); ?>
                    >
                        <?php echo esc_html((string) $column); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <input
                type="checkbox"
                id="<?php echo esc_attr($this->get_field_id('show_search')); ?>"
                name="<?php echo esc_attr($this->get_field_name('show_search')); ?>"
                value="1"
                <?php checked(!empty($instance['show_search'])); ?>
            >
            <label for="<?php echo esc_attr($this->get_field_id('show_search')); ?>">
                <?php esc_html_e('Show subject search', 'nextgen-subjects-widget'); ?>
            </label>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('subjects')); ?>">
                <?php esc_html_e('Subjects:', 'nextgen-subjects-widget'); ?>
            </label>
            <textarea
                class="widefat"
                rows="14"
                id="<?php echo esc_attr($this->get_field_id('subjects')); ?>"
                name="<?php echo esc_attr($this->get_field_name('subjects')); ?>"
            ><?php echo esc_textarea((string) $instance['subjects']); ?></textarea>
            <small>
                <?php esc_html_e('One subject per line. Format: Subject|Description|icon', 'nextgen-subjects-widget'); ?>
            </small>
        </p>

        <?php
    }
}

NextGen_Subjects_Widget_Plugin::init();
