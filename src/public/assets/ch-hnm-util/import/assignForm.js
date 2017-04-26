/*
    window.onload = function () {
        var inputList = $("#hnm-util-import-static-input-list-panel");
        var inputListLis = inputList.find("li");

        inputList.hide();
        inputListLis.each(function() {
            $(this).hide();
        })

        $("input[type=radio]").each(function (i, elem) {
            $(elem).change(function(radio) {

                var radioId = extractIdFromName(this.name);

                if (this.value === "static") {
                    inputListLis.each(function(i, elem) {
                        if (this.id === "static-input-li-for-" + radioId) {
                            $(this).parent().parent().show();
                            $(this).show();
                        }
                    })
                } else {

                }
            });
        })
    }

    function extractIdFromName(name) {
        return name.match(/\[(.*?)\]/)[1];
    }
*/