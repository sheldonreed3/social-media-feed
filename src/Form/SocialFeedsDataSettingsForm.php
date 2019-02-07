<?php

namespace Drupal\social_feeds_data\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SocialFeedsDataSettingsForm extends ConfigFormBase
{
    public function getFormId()
    {
        return 'social_feeds_data_api_settings';
    }

    protected function getEditableConfigNames()
    {
        return [
            'social_feeds_data_api.settings',
        ];
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = [];

        $config = $this->config('social_feeds_data_api.settings');
        
        // Facebook section.
        $form['facebook'] = array(
            '#type' => 'fieldset',
            '#collapsible' => TRUE,
            '#collapsed' => TRUE,
            '#title' => t('Facebook'),
        );
        $form['facebook']['social_feeds_data_fb_sitename'] = array(
            '#type' => 'textfield',
            '#title' => t('Page Name'),
            '#default_value' => $config->get('social_feeds_data_fb_sitename'),
        );
        $form['facebook']['social_feeds_data_fb_app_id'] = array(
            '#type' => 'textfield',
            '#title' => t('App ID'),
            '#default_value' => $config->get('social_feeds_data_fb_app_id'),
        );
        $form['facebook']['social_feeds_data_fb_secret_key'] = array(
            '#type' => 'textfield',
            '#title' => t('Secret Key'),
            '#default_value' => $config->get('social_feeds_data_fb_secret_key'),
        );
        $form['facebook']['social_feeds_data_fb_fields'] = array(
            '#type' => 'textfield',
            '#title' => t('Fields'),
            '#description' => t('Fields to import. See https://developers.facebook.com/docs/graph-api/reference/v2.6/post'),
            '#default_value' => $config->get('social_feeds_data_fb_fields'),
        );
        $form['facebook']['social_feeds_data_fb_count'] = array(
            '#type' => 'textfield',
            '#title' => t('Max Posts'),
            '#default_value' => $config->get('social_feeds_data_fb_count'),
        );
        // Twitter section.
        $form['twitter'] = array(
            '#type' => 'fieldset',
            '#collapsible' => TRUE,
            '#collapsed' => TRUE,
            '#title' => t('Twitter'),
        );
        $form['twitter']['social_feeds_data_twitter_username'] = array(
            '#type' => 'textfield',
            '#title' => t('User Name'),
            '#default_value' => $config->get('social_feeds_data_twitter_username'),
        );
        $form['twitter']['social_feeds_data_twitter_app_id'] = array(
            '#type' => 'textfield',
            '#title' => t('Consumer Key'),
            '#default_value' => $config->get('social_feeds_data_twitter_app_id'),
        );
        $form['twitter']['social_feeds_data_twitter_secret_key'] = array(
            '#type' => 'textfield',
            '#title' => t('Secret Key'),
            '#default_value' => $config->get('social_feeds_data_twitter_secret_key'),
        );
        $form['twitter']['social_feeds_data_twitter_count'] = array(
            '#type' => 'textfield',
            '#title' => t('Max Posts'),
            '#default_value' => $config->get('social_feeds_data_twitter_count'),
        );
        // Instagram section.
        $form['instagram'] = array(
            '#type' => 'fieldset',
            '#collapsible' => TRUE,
            '#collapsed' => TRUE,
            '#title' => t('Instagram'),
        );
        $form['instagram']['social_feeds_data_instagram_username'] = array(
            '#type' => 'textfield',
            '#title' => t('User Name'),
            '#default_value' => $config->get('social_feeds_data_instagram_username'),
        );
        $form['instagram']['social_feeds_data_instagram_client_id'] = array(
            '#type' => 'textfield',
            '#title' => t('Client ID'),
            '#default_value' => $config->get('social_feeds_data_instagram_client_id'),
        );
        $form['instagram']['social_feeds_data_instagram_secret_key'] = array(
            '#type' => 'textfield',
            '#title' => t('Secret Key'),
            '#default_value' => $config->get('social_feeds_data_instagram_secret_key'),
        );
        // TODO Create automated process for updating access_token.
        $form['instagram']['social_feeds_data_instagram_access_token'] = array(
            '#type' => 'textfield',
            '#title' => t('Access Token'),
            '#default_value' => $config->get('social_feeds_data_instagram_access_token'),
        );
        $form['instagram']['social_feeds_data_instagram_count'] = array(
            '#type' => 'textfield',
            '#title' => t('Max Posts'),
            '#default_value' => $config->get('social_feeds_data_instagram_count'),
        );

        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config('social_feeds_data_api.settings')
            ->set('social_feeds_data_fb_sitename', $form_state->getValue('social_feeds_data_fb_sitename'))
            ->set('social_feeds_data_fb_app_id', $form_state->getValue('social_feeds_data_fb_app_id'))
            ->set('social_feeds_data_fb_secret_key', $form_state->getValue('social_feeds_data_fb_secret_key'))
            ->set('social_feeds_data_fb_fields', $form_state->getValue('social_feeds_data_fb_fields'))
            ->set('social_feeds_data_fb_count', $form_state->getValue('social_feeds_data_fb_count'))
            ->set('social_feeds_data_twitter_username', $form_state->getValue('social_feeds_data_twitter_username'))
            ->set('social_feeds_data_twitter_app_id', $form_state->getValue('social_feeds_data_twitter_app_id'))
            ->set('social_feeds_data_twitter_secret_key', $form_state->getValue('social_feeds_data_twitter_secret_key'))
            ->set('social_feeds_data_twitter_count', $form_state->getValue('social_feeds_data_twitter_count'))
            ->set('social_feeds_data_instagram_username', $form_state->getValue('social_feeds_data_instagram_username'))
            ->set('social_feeds_data_instagram_client_id', $form_state->getValue('social_feeds_data_instagram_client_id'))
            ->set('social_feeds_data_instagram_secret_key', $form_state->getValue('social_feeds_data_instagram_secret_key'))
            ->set('social_feeds_data_instagram_access_token', $form_state->getValue('social_feeds_data_instagram_access_token'))
            ->set('social_feeds_data_instagram_count', $form_state->getValue('social_feeds_data_instagram_count'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}