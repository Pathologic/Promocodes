const Promocodes = (function () {
    var CommerceEvent = function(target, name, data) {
        this.event = new CustomEvent(name, {
            cancelable: true,
            bubbles: true,
            detail: data
        });

        target.dispatchEvent(this.event);

        if (window.jQuery) {
            this.$event = $.Event(name);
            jQuery(target).trigger(this.$event, data);
        }

        this.isPrevented = function() {
            if (this.event.defaultPrevented || this.event.returnValue === false) {
                return true;
            }

            if (window.jQuery) {
                if (this.$event.isDefaultPrevented() || typeof this.$event.result != 'undefined' && this.$event.result === false) {
                    return true;
                }
            }

            return false;
        };

        return this;
    };

    function triggerEvent(target, name, data) {
        return new CommerceEvent(target, name, data);
    }

    /**
     * Convert object to FormData, based on object-to-formdata by Parmesh Krishen
     * @link https://github.com/therealparmesh/object-to-formdata
     * @license MIT
     */
    function objectToFormData(object) {
        function isArray(value) {
            return Array.isArray(value);
        }

        function isBlob(value) {
            return value &&
                typeof value.size === 'number' &&
                typeof value.type === 'string' &&
                typeof value.slice === 'function';
        }

        function isFile(value) {
            return isBlob(value) &&
                typeof value.name === 'string' &&
                (typeof value.lastModifiedDate === 'object' ||
                    typeof value.lastModified === 'number');
        }

        function serialize(value, fd, prefix) {
            fd = fd || new FormData();

            if (value === undefined) {
                return fd;
            } else if (value === null) {
                fd.append(prefix, '');
            } else if (typeof value === 'boolean') {
                fd.append(prefix, value);
            } else if (isArray(value)) {
                if (value.length) {
                    value.forEach(function (val, index) {
                        serialize(val, fd, prefix + '[' + index + ']');
                    });
                } else {
                    fd.append(prefix + '[]', '');
                }
            } else if (value instanceof Date) {
                fd.append(prefix, value.toISOString());
            } else if (value === Object(value) && !isFile(value) && !isBlob(value)) {
                Object.keys(value).forEach(function (prop) {
                    var val = value[prop];

                    if (isArray(val)) {
                        while (prop.length > 2 && prop.lastIndexOf('[]') === prop.length - 2) {
                            prop = prop.substring(0, prop.length - 2);
                        }
                    }

                    var key = prefix ? prefix + '[' + prop + ']' : prop;

                    serialize(val, fd, key);
                });
            } else {
                fd.append(prefix, value);
            }

            return fd;
        }

        return serialize(object);
    }

    function request(url, data, callback) {
        if (!data) {
            data = {};
        }

        fetch(url, {
            method: 'POST',
            body: objectToFormData(data)
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {
                if (typeof callback === 'function') callback.call(Commerce, response);
            });
    }

    function delegateEvent(events, selector, handler) {
        if (!(events instanceof Array)) {
            events = [events];
        }

        events.forEach(function(event) {
            document.addEventListener(event, function(e) {
                for (var target = e.target; target && target != this; target = target.parentNode) {
                    if (target.matches(selector)) {
                        handler.call(target, e);
                        break;
                    }
                }
            }, false);
        });
    }

    delegateEvent('click', '[data-promocodes-action]', function(e){
        e.preventDefault();
        const that = this;
        let action = this.getAttribute('data-promocodes-action');
         if(action === 'register' || action === 'remove') {
             let container = this.closest('[data-promocodes]');
             let promocode, instance;
             if (container) {
                let input = container.querySelector('[data-promocodes-instance]');
                if(input) {
                    instance = input.getAttribute('data-promocodes-instance') || 'products';
                    promocode = input.value || '';
                    if(action == 'register') {
                        Promocodes.action('register', {
                            instance: instance,
                            promocode: promocode
                        }, that)
                    } else {
                        input.value = '';
                        Promocodes.action('remove', {
                            instance: instance,
                        }, that)
                    }
                }
             }
         }
    });

    return {
        action: function(action, data, initiator)
        {
            if(!initiator) initiator = document.body;
            let eventName = 'promocode-' + action + '.promocodes';
            let event = triggerEvent(initiator, eventName, data);
            if (event.isPrevented()) {
                alert('Действие отменено обработчиком');
            } else {
                data.action = action;
                request(Commerce.params.path + 'promocodes/action', data, function(response){
                    if(response.status){
                        Commerce.reloadCarts();
                    }
                    let eventName = 'promocode-' + action + '-complete.promocodes';
                    triggerEvent(initiator, eventName, {
                       request: data,
                       response: response
                    });
                });
            }
        },
        register: function(promocode, instance = 'products')
        {
            Commerce.action('register', {
                promocode: promocode,
                instance: instance
            });
        },
        remove: function(instance = 'products')
        {
            Commerce.action('remove', {
                instance: instance
            });
        }
    };
})();

