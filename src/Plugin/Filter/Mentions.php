<?php

namespace Drupal\mentions\Plugin\Filter;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\user\Entity\User;

/**
 * Provides a fallback placeholder filter to use for missing filters.
 *
 * The filter system uses this filter to replace missing filters (for example,
 * if a filter module has been disabled) that are still part of defined text
 * formats. It returns an empty string.
 *
 * @Filter(
 *   id = "mentions",
 *   title = @Translation("Mentions filter"),
 *   description = @Translation("Converts @username and @uid into a link the user's profile page."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = -10
 * )
 */
class Mentions extends FilterBase {

  /**
   * Tracks if an alert about this filter has been logged.
   *
   * @var bool
   */
  protected $logged = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    foreach ($this->findUsers($text) as $match) {
      $text = str_replace($match['text'], $this->linkify($match['user']), $text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t("Converts [@username] and [@#uid] into a link the user's profile page.");
  }

  /**
   * Analyze text for [@username] and [@#uid] references.
   *
   * @param string $text
   *   Text string for analysis.
   *
   * @return array $users
   *   An array of matched text and user accounts.
   */
  protected function findUsers($text) {
    $users = array();
    if (preg_match_all('/\B\[@(\#?.*?)\]/', $text, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $user = (substr($match[1], 0, 1) == '#')
          ? User::load(substr($match[1], 1))
          : user_load_by_name($match[1]);

        if (is_object($user)) {
          $users[] = array(
            'text' => $match[0],
            'user' => $user,
          );
        }
      }
    }

    return $users;
  }

  /**
   * Create a link for a matched user.
   *
   * @param User $user
   *   A user match found in the input text.
   *
   * @return string
   *   A link to the user page.
   */
  protected function linkify(User $user) {
    $url_options = array(
      'attributes' => array(
        'class' => 'mentions mentions-' . $user->id(),
        'title' => $user->getUsername(),
      ),
    );
    $url = Url::fromRoute('entity.user.canonical', array('user' => $user->id()), $url_options);
    return Link::fromTextAndUrl('@' . $user->getUsername(), $url)->toString();
  }

}
