# AI Content for WooCommerce by RapidTextAI

A comprehensive WooCommerce extension that integrates with RapidTextAI to generate high-quality product content using advanced AI models. Create compelling product titles, descriptions, SEO meta data, FAQs, and more with just a few clicks.

## Features

### Core Content Generation
- **Product Titles**: Generate compelling, SEO-friendly product titles
- **Product Descriptions**: Create both short and long product descriptions
- **SEO Meta Data**: Generate optimized meta titles and descriptions
- **Feature Bullets**: Extract and format key product features
- **FAQ Content**: Create relevant frequently asked questions
- **Product Attributes**: Extract attributes from existing descriptions

### Advanced Features
- **Bulk Generation**: Process multiple products simultaneously
- **Content History**: Track all generations with rollback capability
- **Multi-language Support**: Translate content into 10+ languages
- **Custom Templates**: Customize prompts for different content types
- **Brand Voice Control**: Maintain consistent tone across all content

### Integrations
- **Yoast SEO**: Automatic meta field population
- **Rank Math**: Full SEO integration
- **WPML**: Multi-language content generation
- **Polylang**: Translation support
- **Elementor**: AI content widget
- **Advanced Custom Fields**: Custom field support
- **WooCommerce Product Add-ons**: Include add-on data in generation

## Installation

1. **Upload the Plugin**
   - Download the plugin files
   - Upload to `/wp-content/plugins/ai-content-for-woocommerce/`
   - Or install via WordPress admin: Plugins → Add New → Upload Plugin

2. **Activate the Plugin**
   - Go to Plugins → Installed Plugins
   - Find "RapidTextAI for WooCommerce" and click Activate

3. **Connect to RapidTextAI**
   - Go to Settings → RapidTextAI
   - Enter your API key from [RapidTextAI Dashboard](https://app.rapidtextai.com/)
   - Click "Test Connection" to verify

## Getting Started

### 1. Connect Your Account
- Sign up at [RapidTextAI.com](https://app.rapidtextai.com) if you haven't already
- Get your API key from the dashboard
- Enter it in Settings → RapidTextAI → Connection tab

### 2. Configure Settings
- **Model Selection**: Choose your preferred AI model (GPT-4o, Claude, Gemini, etc.)
- **Tone & Style**: Set default tone (professional, friendly, luxury, etc.)
- **Content Templates**: Customize prompts for different content types
- **Safety Filters**: Enable profanity and brand safety filters

### 3. Generate Content

#### Single Product
1. Edit any WooCommerce product
2. Find the "RapidTextAI Composer" meta box in the sidebar
3. Select content types to generate (title, description, SEO, etc.)
4. Customize context (audience, keywords, tone)
5. Click "Generate Content"
6. Preview and apply the generated content

#### Bulk Generation
1. Go to Products → All Products
2. Select multiple products using checkboxes
3. Choose "Generate AI Content" from Bulk Actions dropdown
4. Configure generation options in the modal
5. Start bulk generation and monitor progress

## Configuration Options

### Model Profiles
- **GPT-4o**: Latest OpenAI model with superior reasoning
- **GPT-4o Mini**: Faster, cost-effective option
- **Claude 3.5 Sonnet**: Anthropic's most capable model
- **Gemini 1.5 Pro**: Google's advanced multimodal model
- **DeepSeek Chat**: High-quality, budget-friendly option

### Content Types
- **Title**: Product name optimization
- **Short Description**: Brief product summary (excerpt)
- **Long Description**: Detailed product description
- **SEO Title**: Search engine optimized title
- **SEO Description**: Meta description for search results
- **Bullets**: Key feature bullet points
- **FAQ**: Frequently asked questions
- **Attributes**: Product specifications extraction

### Context Controls
- **Target Audience**: Specify who the content is for
- **Tone**: Professional, friendly, casual, luxury, technical, playful
- **Keywords**: Target keywords for SEO optimization
- **Features**: Highlight specific product features
- **Competitor Analysis**: Reference competing products

## Template Variables

Use these variables in your custom templates:

- `{product_name}` - Product title
- `{categories}` - Product categories
- `{attributes}` - Product attributes
- `{price}` - Product price
- `{audience}` - Target audience
- `{tone}` - Content tone
- `{keywords}` - Target keywords
- `{features}` - Key features
- `{variations}` - Product variations (for variable products)

## Bulk Operations

### Starting Bulk Generation
1. Select products from the products list
2. Choose "Generate AI Content" bulk action
3. Select content types to generate
4. Configure generation options:
   - Tone and audience
   - Focus keywords
   - Overwrite existing content option
5. Review summary and start generation

### Monitoring Progress
- Real-time progress bar
- Job status tracking (queued, running, completed, failed)
- Ability to pause or cancel batch operations
- Detailed completion report

## Content History & Rollback

Every content generation is saved with:
- Generated content
- Model used
- Token count
- Cost estimate
- Timestamp
- User who generated it

### Rollback Feature
- View history of all generations for each product
- One-click rollback to any previous version
- Compare different versions
- Preserve generation metadata

## Multi-language Support

### WPML Integration
- Generate content in multiple languages
- Automatic translation sync
- Language-specific tone settings
- Cultural adaptation

### Polylang Integration
- Full translation support
- Language-specific templates
- Automatic content propagation

### Manual Translation
- Translate any content to 10+ languages
- Maintain brand voice across languages
- Cultural and regional adaptation

## SEO Integration

### Yoast SEO
- Automatic meta title/description population
- Focus keyword integration
- Content analysis compatibility
- Readability optimization

### Rank Math
- Full meta field support
- Schema markup compatibility
- Focus keyword utilization
- SEO score improvement

## API & Extensibility

### REST API Endpoints
- `/wp-json/rtai-wc/v1/generate` - Generate content
- `/wp-json/rtai-wc/v1/apply` - Apply content to product
- `/wp-json/rtai-wc/v1/rollback` - Rollback to previous version
- `/wp-json/rtai-wc/v1/quota` - Check usage quota
- `/wp-json/rtai-wc/v1/jobs` - Monitor generation jobs

### WordPress Hooks
```php
// Modify generation context
add_filter('rtai_wc_prompt_context', function($context, $artifact) {
    // Add custom data to context
    return $context;
}, 10, 2);

// Customize templates
add_filter('rtai_wc_prompt_template_title', function($template) {
    return 'Custom title template: {product_name}';
});

// Post-generation processing
add_action('rtai_wc_content_applied', function($post_id, $artifact, $content) {
    // Custom processing after content application
}, 10, 3);
```

## Performance & Scaling

### Async Processing
- Background job processing using Action Scheduler
- Non-blocking bulk operations
- Automatic retry with exponential backoff
- Concurrency limits to respect API rate limits

### Caching
- Quota information caching (5-minute TTL)
- Template compilation caching
- Duplicate request prevention

### Database Optimization
- Custom jobs table for tracking
- Automatic cleanup of old records
- Efficient indexing for quick lookups

## Troubleshooting

### Common Issues

**"API key is required" error**
- Verify API key is entered correctly in Settings → RapidTextAI
- Ensure your RapidTextAI account is active
- Check for any leading/trailing spaces in the key

**"Quota exceeded" error**
- Check your usage in Settings → RapidTextAI → Usage tab
- Upgrade your RapidTextAI plan if needed
- Monitor usage to avoid overages

**Generation fails or times out**
- Check your internet connection
- Verify RapidTextAI service status
- Try with fewer products in bulk operations
- Contact support if issues persist

**Content not applying to product**
- Check user permissions (must have edit_posts capability)
- Verify product is not locked from AI updates
- Check for conflicts with other plugins
- Review error logs for detailed information

### Debug Mode
Enable debug logging by adding to wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs at `/wp-content/debug.log` for detailed error information.

## Support & Resources

- **Documentation**: [docs.app.rapidtextai.com](https://docs.app.rapidtextai.com)
- **Support**: [support@app.rapidtextai.com](mailto:support@app.rapidtextai.com)
- **Feature Requests**: [GitHub Issues](https://github.com/rapidtextai/woocommerce-plugin)
- **Community**: [Discord](https://discord.gg/rapidtextai)

## Pricing

RapidTextAI operates on a credit-based system:
- **Free Tier**: 1,000 credits/month
- **Starter**: $19/month - 10,000 credits
- **Professional**: $49/month - 30,000 credits
- **Enterprise**: Custom pricing for high-volume usage

Credits are consumed based on:
- Model selected (premium models cost more)
- Content length generated
- Additional features used

## Requirements

- **WordPress**: 5.0 or higher
- **WooCommerce**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **SSL Certificate**: Required for API communication

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Core content generation features
- Bulk operations
- SEO integrations
- Multi-language support
- Content history and rollback
- Template customization
- API integration