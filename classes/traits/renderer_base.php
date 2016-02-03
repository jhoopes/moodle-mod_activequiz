<?php

namespace mod_activequiz\traits;


trait renderer_base {


    /** @var array $pagevars Includes other page information needed for rendering functions */
    protected $pagevars;

    /** @var \moodle_url $pageurl easy access to the pageurl */
    protected $pageurl;

    /** @var \mod_activequiz\activequiz $activequiz */
    protected $activequiz;

    /**
     * Sets a page message to display when the page is loaded into view
     *
     * base_header() must be called for the message to appear
     *
     * @param string $type
     * @param string $message
     */
    public function setMessage($type, $message)
    {
        $this->pageMessage = array($type, $message);
    }

    /**
     * Base header function to do basic header rendering
     *
     * @param string $tab the current tab to show as active
     */
    public function base_header($tab = 'view')
    {
        echo $this->output->header();
        echo activequiz_view_tabs($this->activequiz, $tab);
        $this->showMessage(); // shows a message if there is one
    }

    /**
     * Base footer function to do basic footer rendering
     *
     */
    public function base_footer()
    {
        echo $this->output->footer();
    }

    /**
     * shows a message if there is one
     *
     */
    public function showMessage()
    {

        if ( empty($this->pageMessage) ) {
            return; // return if there is no message
        }

        if ( !is_array($this->pageMessage) ) {
            return; // return if it's not an array
        }

        switch ($this->pageMessage[0]) {
            case 'error':
                echo $this->output->notification($this->pageMessage[1], 'notifiyproblem');
                break;
            case 'success':
                echo $this->output->notification($this->pageMessage[1], 'notifysuccess');
                break;
            case 'info':
                echo $this->output->notification($this->pageMessage[1], 'notifyinfo');
                break;
            default:
                // unrecognized notification type
                break;
        }
    }

    /**
     * Shows an error message with the popup layout
     *
     * @param string $message
     */
    public function render_popup_error($message)
    {

        $this->setMessage('error', $message);
        echo $this->output->header();
        $this->showMessage();
        $this->base_footer();
    }

    /**
     * Initialize the renderer with some variables
     *
     * @param \mod_activequiz\activequiz $activequiz
     * @param \moodle_url $pageurl Always require the page url
     * @param array $pagevars (optional)
     */
    public function init($activequiz, $pageurl, $pagevars = array())
    {
        $this->pagevars = $pagevars;
        $this->pageurl = $pageurl;
        $this->activequiz = $activequiz;
    }

}