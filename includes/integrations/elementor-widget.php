<?php
/**
 * Elementor Widget for RapidTextAI
 */

if (!defined('ABSPATH')) {
    exit;
}

class RTAI_WC_Elementor_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'rtai-content-generator';
    }
    
    public function get_title() {
        return __('RapidTextAI Content', 'ai-content-for-woocommerce');
    }
    
    public function get_icon() {
        return 'eicon-ai';
    }
    
    public function get_categories() {
        return ['woocommerce-elements'];
    }
    
    public function get_keywords() {
        return ['ai', 'content', 'rapidtext', 'woocommerce', 'product'];
    }
    
    protected function register_controls() {
        
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('AI Content Settings', 'ai-content-for-woocommerce'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'content_type',
            [
                'label' => __('Content Type', 'ai-content-for-woocommerce'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'title',
                'options' => [
                    'title' => __('Product Title', 'ai-content-for-woocommerce'),
                    'short_description' => __('Short Description', 'ai-content-for-woocommerce'),
                    'long_description' => __('Long Description', 'ai-content-for-woocommerce'),
                    'bullets' => __('Feature Bullets', 'ai-content-for-woocommerce'),
                    'faq' => __('FAQ Content', 'ai-content-for-woocommerce'),
                ],
            ]
        );
        
        $this->add_control(
            'product_id',
            [
                'label' => __('Product ID', 'ai-content-for-woocommerce'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 0,
                'description' => __('Leave empty to use current product', 'ai-content-for-woocommerce'),
            ]
        );
        
        $this->add_control(
            'auto_generate',
            [
                'label' => __('Auto Generate', 'ai-content-for-woocommerce'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'no',
                'description' => __('Automatically generate content if empty', 'ai-content-for-woocommerce'),
            ]
        );
        
        $this->add_control(
            'show_generate_button',
            [
                'label' => __('Show Generate Button', 'ai-content-for-woocommerce'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show button to generate new content', 'ai-content-for-woocommerce'),
            ]
        );
        
        $this->end_controls_section();
        
        $this->start_controls_section(
            'generation_section',
            [
                'label' => __('Generation Options', 'ai-content-for-woocommerce'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'tone',
            [
                'label' => __('Tone', 'ai-content-for-woocommerce'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Use Default', 'ai-content-for-woocommerce'),
                    'professional' => __('Professional', 'ai-content-for-woocommerce'),
                    'friendly' => __('Friendly', 'ai-content-for-woocommerce'),
                    'casual' => __('Casual', 'ai-content-for-woocommerce'),
                    'luxury' => __('Luxury', 'ai-content-for-woocommerce'),
                    'technical' => __('Technical', 'ai-content-for-woocommerce'),
                    'playful' => __('Playful', 'ai-content-for-woocommerce'),
                ],
            ]
        );
        
        $this->add_control(
            'audience',
            [
                'label' => __('Target Audience', 'ai-content-for-woocommerce'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('e.g., fitness enthusiasts, professionals', 'ai-content-for-woocommerce'),
            ]
        );
        
        $this->add_control(
            'keywords',
            [
                'label' => __('Keywords', 'ai-content-for-woocommerce'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('keyword1, keyword2, keyword3', 'ai-content-for-woocommerce'),
            ]
        );
        
        $this->end_controls_section();
        
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'ai-content-for-woocommerce'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .rtai-elementor-content',
            ]
        );
        
        $this->add_control(
            'content_color',
            [
                'label' => __('Text Color', 'ai-content-for-woocommerce'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rtai-elementor-content' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'content_align',
            [
                'label' => __('Alignment', 'ai-content-for-woocommerce'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'ai-content-for-woocommerce'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'ai-content-for-woocommerce'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'ai-content-for-woocommerce'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __('Justified', 'ai-content-for-woocommerce'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .rtai-elementor-content' => 'text-align: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Get product ID
        $product_id = $settings['product_id'];
        if (empty($product_id)) {
            global $post;
            if ($post && get_post_type($post) === 'product') {
                $product_id = $post->ID;
            }
        }
        
        if (empty($product_id)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="rtai-elementor-placeholder">';
                echo '<p>' . __('RapidTextAI Content Widget', 'ai-content-for-woocommerce') . '</p>';
                echo '<p>' . __('This widget will display AI-generated content for the current product.', 'ai-content-for-woocommerce') . '</p>';
                echo '</div>';
            }
            return;
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        $content_type = $settings['content_type'];
        $content = $this->get_product_content($product, $content_type);
        
        // Auto-generate if enabled and content is empty
        if (empty($content) && $settings['auto_generate'] === 'yes') {
            $content = $this->auto_generate_content($product_id, $content_type, $settings);
        }
        
        echo '<div class="rtai-elementor-widget" data-product-id="' . esc_attr($product_id) . '" data-content-type="' . esc_attr($content_type) . '">';
        
        if (!empty($content)) {
            echo '<div class="rtai-elementor-content">';
            echo wp_kses_post($content);
            echo '</div>';
        } else {
            echo '<div class="rtai-elementor-placeholder">';
            echo '<p>' . __('No content available. Generate content to see it here.', 'ai-content-for-woocommerce') . '</p>';
            echo '</div>';
        }
        
        if ($settings['show_generate_button'] === 'yes') {
            echo '<div class="rtai-elementor-actions">';
            echo '<button type="button" class="rtai-elementor-generate" data-settings="' . esc_attr(json_encode($settings)) . '">';
            echo __('Generate Content', 'ai-content-for-woocommerce');
            echo '</button>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    private function get_product_content($product, $content_type) {
        switch ($content_type) {
            case 'title':
                return $product->get_name();
            case 'short_description':
                return $product->get_short_description();
            case 'long_description':
                return $product->get_description();
            case 'bullets':
                return get_post_meta($product->get_id(), '_rtai_bullets', true);
            case 'faq':
                return get_post_meta($product->get_id(), '_rtai_faq', true);
            default:
                return '';
        }
    }
    
    private function auto_generate_content($product_id, $content_type, $settings) {
        if (!RTAI_WC_API_Client::get_instance()->is_connected()) {
            return '';
        }
        
        try {
            $context_overrides = array();
            if (!empty($settings['tone'])) {
                $context_overrides['tone'] = $settings['tone'];
            }
            if (!empty($settings['audience'])) {
                $context_overrides['audience'] = $settings['audience'];
            }
            if (!empty($settings['keywords'])) {
                $context_overrides['keywords'] = $settings['keywords'];
            }
            
            $composer = RTAI_WC_Composer::get_instance();
            $result = $composer->generate_content($product_id, array($content_type), $context_overrides);
            
            if ($result[$content_type]['success']) {
                $content = $result[$content_type]['content'];
                
                // Auto-apply the content
                $composer->apply_content($product_id, $content_type, $content);
                
                return $content;
            }
        } catch (Exception $e) {
            // Silently fail in auto-generation mode
        }
        
        return '';
    }
    
    protected function content_template() {
        ?>
        <#
        view.addInlineEditingAttributes( 'content', 'advanced' );
        #>
        <div class="rtai-elementor-widget">
            <div class="rtai-elementor-content" {{{ view.getRenderAttributeString( 'content' ) }}}>
                {{{ settings.content_type }}} content will be displayed here
            </div>
            <# if ( settings.show_generate_button === 'yes' ) { #>
            <div class="rtai-elementor-actions">
                <button type="button" class="rtai-elementor-generate">
                    Generate Content
                </button>
            </div>
            <# } #>
        </div>
        <?php
    }
}