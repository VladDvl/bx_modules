CrmSectionCollapse = function (params)
{
    this.userId = parseInt(params.userId, 10);
    if (isNaN(this.userId)) this.userId = 0;
    this.isAdmin = params.isAdmin ? params.isAdmin : false;
    this.optionsData = params.optionsData ? params.optionsData : {};
    this.entity = params.entity ? params.entity : '';

    this.cardSections = null;
    this.sectionsData = {};
};
CrmSectionCollapse.prototype =
{
    initialize: function ()
    {
        this.getSections();
    },
    getSections: function ()
    {
        var obj = this;
        BX.bind(BX(document), 'click', function () {

            obj.cardSections = document.querySelectorAll('div.ui-entity-editor-section, div.ui-entity-editor-section-edit');
            if (obj.cardSections && obj.userId > 0) {
                for (var i=0; i<obj.cardSections.length; i++)
                {
                    var section = obj.cardSections[i];
                    obj.checkSection(section);
                }
            }
        });

        var interval1 = setInterval(function () {

            obj.cardSections = document.querySelectorAll('div.ui-entity-editor-section, div.ui-entity-editor-section-edit');
            if (obj.cardSections && obj.userId > 0) {
                for (var i=0; i<obj.cardSections.length; i++)
                {
                    var section = obj.cardSections[i];
                    obj.checkSection(section, true);
                }
                clearInterval(interval1);
            }
        }, 100);
    },
    checkSection: function (section, firstCheck=false)
    {
        var sectionId = section.dataset.cid;
        var showSection = 'Y';
        if (firstCheck) {
            if (this.optionsData.sections && this.optionsData.users) {
                if (this.optionsData.sections[sectionId] === 'N' &&
                    (this.optionsData.users.includes(this.userId) || this.optionsData.forAll === 'Y')
                ) {
                    showSection = 'N';
                }
            }
        } else {
            showSection = this.sectionsData[sectionId];
        }
        this.sectionsData[sectionId] = showSection;

        if (!BX.hasClass(section, 'section-checked') && sectionId) {
            BX.addClass(section, 'section-checked');

            if (showSection === 'N' && section) {
                var sectionContent = section.querySelector('div.ui-entity-editor-section-content');
                if (sectionContent) {
                    if (!BX.hasClass(sectionContent, 'section-collapse')) {
                        BX.addClass(sectionContent, 'section-collapse');
                    }
                }
            }

            var actionsContainer = section.querySelector('.ui-entity-editor-header-actions');
            var buttons = this.makeSectionButtons(sectionId, showSection);
            BX.append(buttons, actionsContainer);
        }
    },
    makeSectionButtons: function (sectionId, showSection='Y')
    {
        var class1 = 'section-collapse-btn section-btn';
        var class2 = 'section-expand-btn section-btn';

        if (showSection === 'N') {
            class1 += ' hidden';
        } else {
            class2 += ' hidden';
        }

        return BX.create('div', {
            attrs: {
                className: 'section-buttons',
            },
            children: [
                BX.create('span', {
                    attrs: {
                        id: 'collapse-'+sectionId,
                        className: class1,
                        'data-id': sectionId,
                    },
                    text: '-',
                    events: {
                        click: BX.delegate(this.collapseSection, this),
                    },
                }),
                BX.create('span', {
                    attrs: {
                        id: 'expand-'+sectionId,
                        className: class2,
                        'data-id': sectionId,
                    },
                    text: '+',
                    events: {
                        click: BX.delegate(this.expandSection, this),
                    },
                }),
            ],
        });
    },
    collapseSection: function ()
    {
        var elem = BX.proxy_context;
        var sectionId;
        if (elem) {
            sectionId = elem.dataset.id;
        }
        var section;
        if (sectionId) {
            var selectorStr = 'div.ui-entity-editor-section[data-cid="'+sectionId+'"], div.ui-entity-editor-section-edit[data-cid="'+sectionId+'"]';
            section = document.querySelector(selectorStr);
        }
        var sectionContent;
        if (section) {
            sectionContent = section.querySelector('div.ui-entity-editor-section-content');
        }
        if (sectionContent) {
            if (!BX.hasClass(sectionContent, 'section-collapse')) {
                BX.addClass(sectionContent, 'section-collapse');

                this.sectionsData[sectionId] = 'N';

                var btnId = 'expand-'+sectionId;
                var expandBtn = document.getElementById(btnId);

                if (expandBtn) {
                    BX.addClass(elem, 'hidden');

                    if (BX.hasClass(expandBtn, 'hidden')) {
                        BX.removeClass(expandBtn, 'hidden');
                    }
                }
            }
        }

        if (sectionId) {
            this.saveSections();
        }
    },
    expandSection: function ()
    {
        var elem = BX.proxy_context;
        var sectionId;
        if (elem) {
            sectionId = elem.dataset.id;
        }
        var section;
        if (sectionId) {
            var selectorStr = 'div.ui-entity-editor-section[data-cid="'+sectionId+'"], div.ui-entity-editor-section-edit[data-cid="'+sectionId+'"]';
            section = document.querySelector(selectorStr);
        }
        var sectionContent;
        if (section) {
            sectionContent = section.querySelector('div.ui-entity-editor-section-content');
        }
        if (sectionContent) {
            if (BX.hasClass(sectionContent, 'section-collapse')) {
                BX.removeClass(sectionContent, 'section-collapse');

                this.sectionsData[sectionId] = 'Y';

                var btnId = 'collapse-'+sectionId;
                var collapseBtn = document.getElementById(btnId);

                if (collapseBtn) {
                    BX.addClass(elem, 'hidden');

                    if (BX.hasClass(collapseBtn, 'hidden')) {
                        BX.removeClass(collapseBtn, 'hidden');
                    }
                }
            }
        }

        if (sectionId) {
            this.saveSections();
        }
    },
    saveSections: function ()
    {
        var obj = this;
        setTimeout(function () {
            var data = {
                sectionsData: obj.sectionsData,
                forAll: 'N',
                entity: obj.entity,
                userId: obj.userId,
            };

            BX.ajax({
                'url': '/local/modules/vd.crmsectioncollapse/lib/ajax/saveSections.php',
                'method': 'POST',
                'dataType': 'json',
                'data': data,
                onsuccess: BX.delegate(function (response) {
                    //console.log(response);
                }, this),
                onfailure: BX.delegate(function (response) {
                    //console.log(response);
                }, this),
            });

        }, 300);
    },
};

BX.ready(function () {
    var match = window.location.href.match('\/crm\/(.+)\/details\/');
    var entity = false;
    if (match) entity = match[1];
    if (!entity) return;

    BX.ajax({
        'url': '/local/modules/vd.crmsectioncollapse/lib/ajax/getSections.php',
        'method': 'POST',
        'dataType': 'json',
        'data': {entity: entity},
        onsuccess: BX.delegate(function (response) {
            //console.log(response);
            if (!response) return;
            var sectionCollapse = new CrmSectionCollapse(response);
            sectionCollapse.initialize();
            top.CrmSectionCollapse = sectionCollapse;
        }, this),
        onfailure: BX.delegate(function (response) {
            //console.log(response);
        }, this),
    });
});