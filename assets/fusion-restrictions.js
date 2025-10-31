/**
 * Hope Ignites - Fusion Builder Container Restrictions
 * JavaScript for locking containers in the editor
 */

(function($) {
    'use strict';

    // Wait for Fusion Builder to load
    $(document).ready(function() {
        // Check if the localized script data exists
        if (typeof hiFusionRestrictions === 'undefined') {
            console.error('[HI Fusion Restrictions] ERROR: Script data not found. Plugin may not be configured correctly.');
            return;
        }
        
        // Check if restrictions are active
        if (!hiFusionRestrictions.isRestricted) {
            return;
        }

        // Initialize restrictions
        initializeRestrictions();

        // Re-apply restrictions when Fusion Builder updates
        if (window.FusionPageBuilderApp) {
            FusionPageBuilderApp.on('fusion:builder:change', function() {
                setTimeout(initializeRestrictions, 500);
            });
        } else {
            // Wait for Fusion Builder to potentially load
            var fusionCheckInterval = setInterval(function() {
                if (window.FusionPageBuilderApp) {
                    clearInterval(fusionCheckInterval);
                    FusionPageBuilderApp.on('fusion:builder:change', function() {
                        setTimeout(initializeRestrictions, 500);
                    });
                }
            }, 1000);
            
            // Stop checking after 10 seconds
            setTimeout(function() {
                clearInterval(fusionCheckInterval);
            }, 10000);
        }

        // Watch for DOM changes (for live editor)
        observeBuilderChanges();
        
        // Periodic re-check for containers (in case they load late)
        setTimeout(function() {
            initializeRestrictions();
        }, 2000);
        
        setTimeout(function() {
            initializeRestrictions();
        }, 5000);
    });

    /**
     * Initialize container restrictions
     */
    function initializeRestrictions() {
        var lockedClasses = hiFusionRestrictions.lockedClasses || [];
        var message = hiFusionRestrictions.message || 'This section is locked';
        
        if (lockedClasses.length === 0) {
            console.warn('[HI Fusion Restrictions] WARNING: No locked classes defined!');
            return;
        }

        // Find all containers with locked classes
        lockedClasses.forEach(function(lockedClass) {
            // Backend editor (shortcode-based)
            findAndLockContainers(lockedClass, message);
            
            // Live editor (if active)
            findAndLockLiveContainers(lockedClass, message);
        });
    }

    /**
     * Find and lock containers in backend editor
     */
    function findAndLockContainers(lockedClass, message) {
        // Look for fusion_builder_container shortcodes with the locked class
        var $textareas = $('.wp-editor-area, #content');
        var foundInShortcode = false;
        
        $textareas.each(function() {
            var content = $(this).val();
            if (!content) return;
            
            // Check if content has locked containers
            var pattern = new RegExp('\\[fusion_builder_container[^\\]]*class=["\'][^"\']*' + lockedClass + '[^"\']*["\']', 'gi');
            if (pattern.test(content)) {
                foundInShortcode = true;
                showEditorWarning(message);
            }
        });

        // Visual indicators in Fusion Builder preview
        var $containers = $('.fusion-builder-container');
        $containers.each(function() {
            var $container = $(this);
            var containerClass = $container.attr('class') || '';
            
            if (containerClass.includes(lockedClass)) {
                lockContainer($container, message);
            }
        });
    }

    /**
     * Find and lock containers in live editor
     */
    function findAndLockLiveContainers(lockedClass, message) {
        var allDirectElements = [];
        
        // Strategy 1: Search main document
        var nativeElements = document.querySelectorAll('.' + lockedClass);
        
        // Strategy 2: Search top window document (if different)
        try {
            if (window.top && window.top.document && window.top !== window.self) {
                var topDocElements = window.top.document.querySelectorAll('.' + lockedClass);
                for (var t = 0; t < topDocElements.length; t++) {
                    allDirectElements.push(topDocElements[t]);
                }
            }
        } catch (e) {
            // Cross-origin or other issue - silently continue
        }
        
        // Strategy 3: Search all iframes
        var iframes = document.querySelectorAll('iframe');
        iframes.forEach(function(iframe) {
            try {
                var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                if (iframeDoc) {
                    var iframeElements = iframeDoc.querySelectorAll('.' + lockedClass);
                    for (var i = 0; i < iframeElements.length; i++) {
                        allDirectElements.push(iframeElements[i]);
                    }
                }
            } catch (e) {
                // Cross-origin - silently continue
            }
        });
        
        // Add elements from main document
        for (var m = 0; m < nativeElements.length; m++) {
            allDirectElements.push(nativeElements[m]);
        }
        
        // Strategy 4: jQuery selector
        var $directElements = $('.' + lockedClass);
        $directElements.each(function() {
            if (allDirectElements.indexOf(this) === -1) {
                allDirectElements.push(this);
            }
        });
        
        // Process all found elements
        allDirectElements.forEach(function(el) {
            var $element = $(el);
            
            // Find the container element - prioritize locking the highest level container
            var $container = $element;
            
            // If this is already a top-level container, use it directly
            if ($element.hasClass('fusion-fullwidth')) {
                $container = $element;
            } else if ($element.hasClass('fusion_builder_container') || $element.hasClass('fusion-builder-row')) {
                $container = $element;
            } else {
                // Try to find parent container - prioritize fusion-fullwidth (highest level)
                var $parentFullwidth = $element.closest('.fusion-fullwidth');
                if ($parentFullwidth.length > 0) {
                    $container = $parentFullwidth;
                } else {
                    $container = $element.closest('.fusion_builder_container, [data-element_type="fusion_builder_container"], .fusion-builder-row');
                    
                    if ($container.length === 0) {
                        $container = $element.closest('.fusion-layout-column, .fusion_builder_column');
                    }
                    
                    if ($container.length === 0) {
                        $container = $element;
                    }
                }
            }
            
            // Lock the container (avoid duplicates by checking if already locked)
            var containerEl = $container.first()[0];
            if (containerEl && !containerEl.classList.contains('hi-locked-container')) {
                lockLiveContainer($container.first(), message);
            }
        });
        
        // Check data attributes for containers and text blocks
        var $dataContainers = $('[data-element_type="fusion_builder_container"], [data-type="fusion_builder_container"], [data-element_type="fusion_text"], [data-type="fusion_text"]');
        
        $dataContainers.each(function() {
            var $element = $(this);
            var elementClass = $element.attr('class') || '';
            var params = $element.data('params') || {};
            var containerClass = params.class || '';
            var allClasses = elementClass + ' ' + containerClass;
            
            // Also check all data attributes for the class
            var dataAttributes = $element[0].attributes;
            for (var i = 0; i < dataAttributes.length; i++) {
                var attr = dataAttributes[i];
                if (attr.name.startsWith('data-') && attr.value) {
                    allClasses += ' ' + attr.value;
                }
            }
            
            if (allClasses.includes(lockedClass)) {
                lockLiveContainer($element, message);
            }
        });
        
        // Check Fusion Builder live elements
        var $liveElements = $('.fusion-builder-live-element, .fusion_builder_container, .fusion-fullwidth');
        $liveElements.each(function() {
            var $element = $(this);
            var elementClass = $element.attr('class') || '';
            
            if (elementClass.includes(lockedClass) && !$element.hasClass('hi-locked-container')) {
                lockLiveContainer($element, message);
            }
        });
        
        // Check any element with fusion-builder-* classes that has the locked class
        var $fusionElements = $('[class*="fusion"], [class*="avada"]');
        $fusionElements.each(function() {
            var $element = $(this);
            var elementClass = $element.attr('class') || '';
            
            if (elementClass.includes(lockedClass) && elementClass.match(/fusion|avada/i) && !$element.hasClass('hi-locked-container')) {
                lockLiveContainer($element, message);
            }
        });
    }

    /**
     * Lock a container in backend editor
     */
    function lockContainer($container, message) {
        if ($container.hasClass('hi-locked-container')) {
            return; // Already locked
        }

        $container.addClass('hi-locked-container');
        
        // Add lock notice if not already present
        if ($container.find('.hi-locked-notice').length === 0) {
            var $notice = $('<div class="hi-locked-notice"></div>').text(message);
            $container.prepend($notice);
        }

        // Hide/disable controls
        var controlsSelectors = ['.fusion-builder-controls', '.fusion-builder-remove', '.fusion-builder-clone', '.fusion-builder-settings'];
        controlsSelectors.forEach(function(selector) {
            $container.find(selector).hide();
        });
        
        // Prevent editing
        $container.off('click.hi-restriction').on('click.hi-restriction', function(e) {
            if ($(e.target).closest('.hi-locked-notice').length === 0) {
                e.preventDefault();
                e.stopPropagation();
                alert(message);
                return false;
            }
        });

        // Disable dragging
        $container.attr('draggable', 'false');
        $container.css('cursor', 'not-allowed');
    }

    /**
     * Lock a container in live editor
     */
    function lockLiveContainer($element, message) {
        if ($element.hasClass('hi-locked-container')) {
            return; // Already locked
        }

        $element.addClass('hi-locked-container');
        
        // Add overlay for live editor
        if ($element.find('.hi-locked-overlay').length === 0) {
            var $overlay = $('<div class="hi-locked-overlay"></div>');
            $overlay.html('<div class="hi-locked-notice">' + message + '</div>');
            $element.append($overlay);
        }

        // Prevent all interactions
        $element.off('click.hi-restriction dblclick.hi-restriction mousedown.hi-restriction')
                .on('click.hi-restriction dblclick.hi-restriction mousedown.hi-restriction', function(e) {
            if (!$(e.target).hasClass('hi-locked-notice')) {
                e.preventDefault();
                e.stopPropagation();
                alert(message);
                return false;
            }
        });

        // Hide element controls
        $element.find('.fusion-builder-controls, .fusion-builder-module-controls').hide();
    }

    /**
     * Show warning in editor
     */
    function showEditorWarning(message) {
        // Only show once
        if ($('#hi-fusion-warning').length > 0) {
            return;
        }
        
        var $warning = $('<div id="hi-fusion-warning" class="notice notice-warning"></div>');
        $warning.html(
            '<p><strong>⚠️ Content Restriction Active:</strong> ' +
            'This page contains locked sections. ' + message + '</p>'
        );
        
        var $target = $('.wrap h1');
        if ($target.length > 0) {
            $target.after($warning);
        } else {
            console.warn('[HI Fusion Restrictions] Could not find target element to insert warning');
            $('body').prepend($warning);
        }
    }

    /**
     * Observe changes to the builder (for dynamic loading)
     */
    function observeBuilderChanges() {
        var target = document.querySelector('#fusion_builder_container') || document.body;
        
        if (!target) {
            return;
        }
        
        // Ensure the target is a Node (defensive against odd environments)
        if (typeof Node !== 'undefined' && !(target instanceof Node)) {
            console.warn('[HI Fusion Restrictions] Target is not a Node, skipping observer');
            return;
        }
        
        if (!window.MutationObserver) {
            return;
        }

        var observer = new MutationObserver(function(mutations) {
            var shouldReapply = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            var $node = $(node);
                            
                            // Check if it's a Fusion Builder element
                            var isFusionElement = $node.hasClass('fusion-builder-container') || 
                                                  $node.hasClass('fusion-builder-live-element') ||
                                                  $node.hasClass('fusion_builder_container') ||
                                                  $node.hasClass('fusion-fullwidth') ||
                                                  $node.attr('data-element_type') === 'fusion_builder_container' ||
                                                  $node.find('.fusion-builder-container, .fusion-builder-live-element, .fusion_builder_container').length > 0;
                            
                            // Also check if any added node has the locked classes
                            var hasLockedClass = false;
                            if (node.classList) {
                                node.classList.forEach(function(className) {
                                    if (className === 'nhq-locked' || className === 'nhq-critical') {
                                        hasLockedClass = true;
                                    }
                                });
                            }
                            
                            // Check descendants for locked classes
                            if (!hasLockedClass && $node.find('.nhq-locked, .nhq-critical').length > 0) {
                                hasLockedClass = true;
                            }
                            
                            if (isFusionElement || hasLockedClass) {
                                shouldReapply = true;
                            }
                        }
                    });
                }
                
                // Also check attribute changes (in case classes are added dynamically)
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    var targetClasses = mutation.target.className || '';
                    if (targetClasses.includes('nhq-locked') || targetClasses.includes('nhq-critical')) {
                        shouldReapply = true;
                    }
                }
            });
            
            if (shouldReapply) {
                setTimeout(initializeRestrictions, 500);
            }
        });

        try {
            observer.observe(target, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class']
            });
        } catch (err) {
            console.error('[HI Fusion Restrictions] Failed to start MutationObserver:', err && err.message ? err.message : err);
        }
    }

    /**
     * Intercept save attempts with locked content
     */
    $(document).on('click', '#publish, #save-post', function(e) {
        if (!hiFusionRestrictions || !hiFusionRestrictions.isRestricted) {
            return true;
        }

        var $button = $(this);
        var lockedClasses = hiFusionRestrictions.lockedClasses || [];
        var content = '';
        
        // Get content from editor
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.getContent) {
            content = wp.editor.getContent('content');
        } else {
            content = $('#content').val() || '';
        }

        // Check if any locked containers exist in content
        var hasLockedContent = false;
        lockedClasses.forEach(function(lockedClass) {
            var pattern = new RegExp('\\[fusion_builder_container[^\\]]*class=["\'][^"\']*' + lockedClass, 'gi');
            if (pattern.test(content)) {
                hasLockedContent = true;
            }
        });

        if (hasLockedContent) {
            // Show warning but allow save (server-side will validate)
            if (!$button.data('warned')) {
                $button.data('warned', true);
                
                var confirmed = confirm(
                    'This page contains restricted sections. ' +
                    'If you modified any locked containers, your changes will not be saved. ' +
                    'Do you want to continue?'
                );
                
                if (!confirmed) {
                    e.preventDefault();
                    return false;
                }
            }
        }

        return true;
    });

})(jQuery);
