<?php

namespace Hizzle\Noptin\Tests\Objects;

use Hizzle\Noptin\Objects\Generic_Post_Type;

/**
 * Test Generic_Post_Type class.
 */
class Test_Generic_Post_Type extends \WP_UnitTestCase {

    /**
     * @var Generic_Post_Type
     */
    protected $post_type;

    /**
     * Set up test environment.
     */
    public function set_up() {
        parent::set_up();
        $this->post_type = \Hizzle\Noptin\Objects\Store::get( 'post' );
    }

    /**
     * Test constructor.
     */
    public function test_constructor() {
        $this->assertEquals('post', $this->post_type->type);
        $this->assertEquals('wordpress', $this->post_type->integration);
        $this->assertEquals('\Hizzle\Noptin\Objects\Generic_Post', $this->post_type->record_class);
    }

    /**
     * Test get_filters method.
     */
    public function test_get_filters() {
        $filters = $this->post_type->get_filters();
        
        $this->assertIsArray($filters);
        $this->assertArrayHasKey('author', $filters);
        $this->assertArrayHasKey('comment_count', $filters);
        $this->assertArrayHasKey('s', $filters);
    }

    /**
     * Test get_all method.
     */
    public function test_get_all() {
        // Create test posts
        $post1 = $this->factory->post->create();
        $post2 = $this->factory->post->create();

        $posts = $this->post_type->get_all([]);

        $this->assertIsArray($posts);
        $this->assertContains($post1, $posts);
        $this->assertContains($post2, $posts);
    }

    /**
     * Test create_post method.
     */
    public function test_create_post() {
        $args = [
            'title'   => 'Test Post',
            'content' => 'Test content',
            'status'  => 'publish'
        ];

        $post_id = $this->post_type->create_post($args);

        $this->assertIsNumeric($post_id);

        $post = get_post($post_id);
        $this->assertEquals('Test Post', $post->post_title);
        $this->assertEquals('Test content', $post->post_content);
        $this->assertEquals('publish', $post->post_status);
    }

    /**
     * Test delete_post method.
     */
    public function test_delete_post() {
        // Create a test post
        $post_id = $this->factory->post->create();

        // Delete the post
        $result = $this->post_type->delete_post(['id' => $post_id, 'force_delete' => true]);

        $this->assertInstanceOf(\WP_Post::class, $result);
        $this->assertNull(get_post($post_id));
    }

    /**
     * Test get_fields method.
     */
    public function test_get_fields() {
        $fields = $this->post_type->get_fields();

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('title', $fields);
        $this->assertArrayHasKey('content', $fields);
        $this->assertArrayHasKey('status', $fields);
    }

    /**
     * Test create_post with taxonomies.
     */
    public function test_create_post_with_taxonomies() {
        // Create a test category
        $category = $this->factory->term->create(['taxonomy' => 'category']);

        $args = [
            'title'     => 'Test Post',
            'tax_category' => [$category]
        ];

        $post_id = $this->post_type->create_post($args);

        $this->assertIsNumeric($post_id);
        $categories = wp_get_post_categories($post_id);
        $this->assertContains($category, $categories);
    }

    /**
     * Test create_post with custom fields.
     */
    public function test_create_post_with_custom_fields() {
        $args = [
            'title'      => 'Test Post',
            'custom_key' => 'custom_value'
        ];

        $post_id = $this->post_type->create_post($args);

        $this->assertIsNumeric($post_id);
        $this->assertEquals('custom_value', get_post_meta($post_id, 'custom_key', true));
    }


    /**
     * Test filtering posts by taxonomy.
     */
    public function test_get_all_with_taxonomy_filter() {
        // Create test categories
        $category1 = $this->factory->term->create([
            'taxonomy' => 'category',
            'name'    => 'Category 1'
        ]);
        $category2 = $this->factory->term->create([
            'taxonomy' => 'category',
            'name'    => 'Category 2'
        ]);

        // Create test tags
        $tag1 = $this->factory->term->create([
            'taxonomy' => 'post_tag',
            'name'    => 'Tag 1'
        ]);
        $tag2 = $this->factory->term->create([
            'taxonomy' => 'post_tag',
            'name'    => 'Tag 2'
        ]);

        // Create posts with different taxonomies
        $post1 = $this->factory->post->create([
            'post_title'    => 'Post in Category 1 with Tag 1',
            'post_status'   => 'publish',
        ]);
        wp_set_post_categories($post1, [$category1]);
        wp_set_post_tags($post1, [$tag1]);

        $post2 = $this->factory->post->create([
            'post_title'    => 'Post in Category 2 with Tag 2',
            'post_status'   => 'publish',
        ]);
        wp_set_post_categories($post2, [$category2]);
        wp_set_post_tags($post2, [$tag2]);

        $post3 = $this->factory->post->create([
            'post_title'    => 'Post in Both Categories and Tags',
            'post_status'   => 'publish',
        ]);
        wp_set_post_categories($post3, [$category1, $category2]);
        wp_set_post_tags($post3, [$tag1, $tag2]);

        // Test filtering by category__in
        $filtered_posts = $this->post_type->get_all([
            'tax_in_category' => [$category1]
        ]);

        $this->assertIsArray($filtered_posts);
        $this->assertContains($post1, $filtered_posts);
        $this->assertContains($post3, $filtered_posts);
        $this->assertNotContains($post2, $filtered_posts);

        // Test filtering by category__not_in
        $filtered_posts = $this->post_type->get_all([
            'tax_not_in_category' => [$category1]
        ]);

        $this->assertIsArray($filtered_posts);
        $this->assertContains($post2, $filtered_posts);
        $this->assertNotContains($post1, $filtered_posts);
        $this->assertNotContains($post3, $filtered_posts);

        // Test filtering by tag__in
        $filtered_posts = $this->post_type->get_all([
            'tax_in_post_tag' => [$tag1]
        ]);

        $this->assertIsArray($filtered_posts);
        $this->assertContains($post1, $filtered_posts);
        $this->assertContains($post3, $filtered_posts);
        $this->assertNotContains($post2, $filtered_posts);

        // Test filtering by custom taxonomy
        $custom_tax = 'test_taxonomy';
        register_taxonomy($custom_tax, 'post');

        $term1 = $this->factory->term->create([
            'taxonomy' => $custom_tax,
            'name'    => 'Term 1'
        ]);

        $post4 = $this->factory->post->create([
            'post_title'    => 'Post with Custom Taxonomy',
            'post_status'   => 'publish',
        ]);
        wp_set_object_terms($post4, [$term1], $custom_tax);

        $filtered_posts = $this->post_type->get_all([
            'tax_in_' . $custom_tax => [$term1]
        ]);

        $this->assertIsArray($filtered_posts);
        $this->assertContains($post4, $filtered_posts);
        $this->assertNotContains($post1, $filtered_posts);

        // Test filtering by multiple taxonomies
        $filtered_posts = $this->post_type->get_all([
            'tax_in_category' => [$category1],
            'tax_in_post_tag' => [$tag2]
        ]);

        $this->assertIsArray($filtered_posts);
        $this->assertContains($post3, $filtered_posts);
        $this->assertNotContains($post1, $filtered_posts);
        $this->assertNotContains($post2, $filtered_posts);

        // Test excluding by custom taxonomy
        $filtered_posts = $this->post_type->get_all([
            'tax_not_in_' . $custom_tax => [$term1]
        ]);

        $this->assertIsArray($filtered_posts);
        $this->assertNotContains($post4, $filtered_posts);
        $this->assertContains($post1, $filtered_posts);
    }

}
