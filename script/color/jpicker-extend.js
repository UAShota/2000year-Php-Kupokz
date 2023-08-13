(function ($)
{
    var jPickerEx =
    {
        rgb2hex: function(rgb)
        {
            rgb = rgb.match(/^rgb[a]?\((\d+),\s*(\d+),\s*(\d+)(.+?([0-9]*\.?[0-9]*))?\)$/);
            val = new Object();
            val.r = rgb[1];
            val.g = rgb[2];
            val.b = rgb[3];
            val.a = rgb[5] != undefined ? rgb[5] * 255 : 255;
            return val;
        },

        localVector: new Array(),

        assign: function(vector)
        {
            for (index = 0; index < vector.length; index++) {
                this.assignTo(vector[index]);
            }
            return this;
        },

        assignTo: function(vectorData)
        {
            // todo $
            this.localVector.push(vectorData);
            var jCssname = vectorData[2];
            var jContainer = vectorData[1];
            var jControl = vectorData[0];

            if (vectorData[3] == false) {
                return false;
            }
            if ($(jContainer).length == 0) {
                $(jControl).html("<b>Недоступно</b>");
                return false;
            }
            var color = this.rgb2hex($(jContainer).css(jCssname));

            $(jControl).jPicker({
                images:  {
                    clientPath: "/script/color/images/",
                },
                window:
                {
                    expandable: true,
                    position: {
                        y: 'bottom'
                    },
                    effects: {
                        type: 'fade',
                        speed: {
                            show: 'fast',
                            hide: 'fast'
                        }
                    },
                    alphaSupport: true
                },
                color: {
                    active: new $.jPicker.Color({ r: color.r, g: color.g, b: color.b, a: color.a})
                },
                localization:
                {
                    text:
                    {
                        title: 'Переместите маркер для выбора цвета',
                        newColor: 'новый',
                        currentColor: 'текущий',
                        ok: 'Применить',
                        cancel: 'Отмена'
                    },
                    tooltips:
                    {
                        colors:
                        {
                            newColor: 'Новый цвет - нажмите “Применить” для сохранения',
                            currentColor: 'Нажмите для возвращения оригинального цвета'
                        },
                        buttons:
                        {
                            ok: 'Применить выбранный цвет',
                            cancel: 'Отменить выбор цвета и вернуть оригинальный цвет'
                        },
                        hue:
                        {
                            radio: 'Настройка цвета',
                            textbox: 'Укажите значение (0-360°)'
                        },
                        saturation:
                        {
                            radio: 'Настройка насыщенности',
                            textbox: 'Укажите значение (0-100%)'
                        },
                        value:
                        {
                            radio: 'Настройки яркости',
                            textbox: 'Укажите значение (0-100%)'
                        },
                        red:
                        {
                            radio: 'Значение красного цвета',
                            textbox: 'Укажите значение (0-255)'
                        },
                        green:
                        {
                            radio: 'Настройка зеленого цвета',
                            textbox: 'Укажите значение (0-255)'
                        },
                        blue:
                        {
                            radio: 'Настройка синего цвета',
                            textbox: 'Укажите значение (0-255)'
                        },
                        alpha:
                        {
                            radio: 'Настройка прозрачности',
                            textbox: 'Укажите значение (0-100)'
                        },
                        hex:
                        {
                            textbox: 'Укажите 16-тиричный код цвета (#000000-#ffffff)',
                            alpha: 'Укажите значение прозрачности (#00-#ff)'
                        }
                    }
                }
            },
            function(color, context) {
                $("#submit").attr("disabled", false);
            },
            function(color, context)
            {
                var hex = color.val('rgba');
                $(jContainer).css(jCssname, "rgba("+hex.r+","+hex.g+","+hex.b+","+(hex.a/255)+")");
            });

            return true;
        }
    }
    $.extend($.jPicker, jPickerEx);
})(jQuery);
