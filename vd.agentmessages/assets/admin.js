var Agentmessages = BX.namespace('VD.Agentmessages');

BX.Vue.component('user-selector', {
    props: ['agent_key', 'schedule', 'users', 'departments'],
    data: function () {
        return {
            dialogObj: null,
            dialogContainer: null,
        }
    },
    methods: {
        setItem(itemId, entityId, remove = false)
        {
            var params = {
                itemId: itemId,
                entityId: entityId,
                agent_key: this.agent_key,
            };
            if (remove === true) {
                this.$emit('onDeselectEntity', params);
            } else {
                this.$emit('onSelectEntity', params);
            }
        },
    },
    mounted() {
        var users = this.schedule['users'];
        if (!users) users = [];
        var departments = this.schedule['departments'];
        if (!departments) departments = [];

        var obj = this;
        var container = document.getElementById('user-selector-'+this.agent_key);
        if (container) {
            var options = {
                id: 'entity-selector-'+this.agent_key,
                dialogOptions: {
                    id: 'selector-dialog'+this.agent_key,
                    entities: [
                        {
                            id: 'user',
                        },
                        {
                            id: 'department',
                            options: {
                                selectMode: 'usersAndDepartments',
                            }
                        },
                    ],
                    multiple: true,
                    dropdownMode: true,
                    searchOptions: {
                        allowCreateItem: false,
                    },
                },
                events: {
                    onAfterTagAdd: function (e) {
                        var tag = e.getData();
                        if (!tag) return;
                        var tagObj = tag.tag;
                        if (!tagObj) return;

                        var itemId = parseInt(tagObj.id, 10);
                        if (isNaN(itemId)) itemId = 0;
                        var entityId = tagObj.entityId;
                        if (itemId === 0 || !entityId) return;

                        obj.setItem(itemId, entityId);
                    },
                    onAfterTagRemove: function (e) {
                        var tag = e.getData();
                        if (!tag) return;
                        var tagObj = tag.tag;
                        if (!tagObj) return;

                        var itemId = parseInt(tagObj.id, 10);
                        if (isNaN(itemId)) itemId = 0;
                        var entityId = tagObj.entityId;
                        if (itemId === 0 || !entityId) return;

                        obj.setItem(itemId, entityId, true);
                    },
                },
                multiple: true,
                showAddButton: true,
                showCreateButton: false,
                showTextBox: false,
                items: [],
            };

            if (users.length > 0) {
                for (var n = 0; n < users.length; n++)
                {
                    var uId = parseInt(users[n]);
                    if (isNaN(uId)) uId = 0;
                    if (uId === 0) continue;

                    var uName = obj['users'][uId];
                    if (!uName) uName = uId;

                    options.items.push({
                        id: uId,
                        entityId: 'user',
                        entityType: 'employee',
                        title: {text: uName},
                    });
                }
            }
            if (departments.length > 0) {
                for (var m = 0; m < departments.length; m++)
                {
                    var depId = parseInt(departments[m]);
                    if (isNaN(depId)) depId = 0;
                    if (depId === 0) continue;

                    var depName = obj['departments'][depId];
                    if (!depName) depName = depId;

                    options.items.push({
                        id: depId,
                        entityId: 'department',
                        entityType: 'default',
                        title: {text: depName},
                    });
                }
            }

            var selector = new BX.UI.EntitySelector.TagSelector(options);
            if (selector) {
                selector.renderTo(container);

                this.dialogObj = selector.getDialog();
                if (this.dialogObj) {
                    this.dialogContainer = this.dialogObj.getContainer();
                }
                var footerElements;
                if (this.dialogContainer) {
                    footerElements = this.dialogContainer.querySelectorAll('.ui-selector-footer-container');
                }
                if (footerElements) {
                    for (var i = 0; i < footerElements.length; i++)
                    {
                        BX.hide(footerElements[i]);
                    }
                }
            }
        }
    },
    template: '<div :id="\'user-selector-\'+agent_key" class="user-selector-container"></div>',
});

BX.Vue.component('schedule-list', {
    props: ['params'],
    data: function () {
        return {
            default_schedule: this.params.default_schedule,
            schedule_list: (this.params.schedule_list) ? this.params.schedule_list : {},
            agent_list: (this.params.agent_list) ? this.params.agent_list : {},
            fields: this.params.settings.fields,
            agent_messages: this.params.settings.agent_messages,
            agent_buttons: this.params.settings.buttons,
            controls: this.params.settings.controls,
            messages: {},
            userNames: this.params['userNames'],
            departmentNames: this.params['departmentNames'],
            calendarObj: null,
        }
    },
    methods: {
        genNewName(length=16)
        {
            var name = '';
            var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var charactersLength = characters.length;
            for (var i = 0; i < length; i++)
            {
                name += characters.charAt(Math.floor(Math.random()*charactersLength));
            }
            return name;
        },
        handleControl(fName)
        {
            this[fName]();
        },
        Add()
        {
            var scheduleName = this.genNewName();
            var newSchedule = JSON.parse(JSON.stringify(this.default_schedule));
            newSchedule.id = scheduleName;
            this.$set(this.schedule_list, scheduleName, newSchedule);
            setTimeout(function () {
                var element = document.querySelector('div[data-id="'+scheduleName+'"]');
                if (element) element.scrollIntoView({block: "center", behavior: "smooth"});
            }, 100);
        },
        getFields(agent_key)
        {
            return this.schedule_list[agent_key];
        },
        handleButton(name, agent_key)
        {
            this[name](agent_key);
        },
        Save(agent_key)
        {
            var fields = this.getFields(agent_key);

            var obj = this;
            BX.ajax.runAction('vd:agentmessages.AdminController.save', {
                data: {
                    data: {
                        scheduleId: agent_key,
                        fields: fields,
                    },
                }
            }).then(function(response) {
                //console.log(response);
                var data = response['data'];
                if (!data) return;
                obj.handleResponse(data);
            });
        },
        Delete(agent_key)
        {
            var res = confirm(this.agent_buttons.delete.name+'?');
            if (res) {

                var obj = this;
                BX.ajax.runAction('vd:agentmessages.AdminController.delete', {
                    data: {
                        data: {
                            scheduleId: agent_key,
                        },
                    }
                }).then(function(response) {
                    //console.log(response);
                    var data = response['data'];
                    if (!data) return;
                    obj.handleResponse(data);
                });
            }
        },
        EnableAgent(agent_key)
        {
            var fields = this.getFields(agent_key);

            var obj = this;
            BX.ajax.runAction('vd:agentmessages.AdminController.enableAgent', {
                data: {
                    data: {
                        scheduleId: agent_key,
                        fields: fields,
                    },
                }
            }).then(function(response) {
                //console.log(response);
                var data = response['data'];
                if (!data) return;
                obj.handleResponse(data);
            });
        },
        DisableAgent(agent_key)
        {
            var obj = this;
            BX.ajax.runAction('vd:agentmessages.AdminController.disableAgent', {
                data: {
                    data: {
                        scheduleId: agent_key,
                    },
                }
            }).then(function(response) {
                //console.log(response);
                var data = response['data'];
                if (!data) return;
                obj.handleResponse(data);
            });
        },
        handleResponse(data)
        {
            var errorMessage = data['errorMessage'];
            if (errorMessage) {
                alert(errorMessage);
                return;
            }

            var result = data['result'];
            if (result === true) {
                location.reload();
            }
        },
        onDeselectEntity(params)
        {
            var agent_key = params.agent_key;
            if (!agent_key) return;
            if (!this.schedule_list[agent_key]) return;
            var itemId = parseInt(params.itemId, 10);
            if (isNaN(itemId)) itemId = 0;
            var entityId = params.entityId;
            if (!itemId || !entityId) return;
            itemId = itemId.toString();

            if (!this.schedule_list[agent_key]['users']) {
                this.schedule_list[agent_key]['users'] = [];
            }
            if (!this.schedule_list[agent_key]['departments']) {
                this.schedule_list[agent_key]['departments'] = [];
            }

            var idx;
            if (entityId === 'user') {
                idx = this.schedule_list[agent_key]['users'].indexOf(itemId);
                if (idx !== -1) {
                    this.schedule_list[agent_key]['users'].splice(idx, 1);
                }
            } else if (entityId === 'department') {
                idx = this.schedule_list[agent_key]['departments'].indexOf(itemId);
                if (idx !== -1) {
                    this.schedule_list[agent_key]['departments'].splice(idx, 1);
                }
            }
        },
        onSelectEntity(params)
        {
            var agent_key = params.agent_key;
            if (!agent_key) return;
            if (!this.schedule_list[agent_key]) return;
            var itemId = parseInt(params.itemId, 10);
            if (isNaN(itemId)) itemId = 0;
            var entityId = params.entityId;
            if (!itemId || !entityId) return;
            itemId = itemId.toString();

            if (!this.schedule_list[agent_key]['users']) {
                this.schedule_list[agent_key]['users'] = [];
            }
            if (!this.schedule_list[agent_key]['departments']) {
                this.schedule_list[agent_key]['departments'] = [];
            }

            if (entityId === 'user') {
                if (this.schedule_list[agent_key]['users'].indexOf(itemId) === -1) {
                    this.schedule_list[agent_key]['users'].push(itemId);
                }
            } else if (entityId === 'department') {
                if (this.schedule_list[agent_key]['departments'].indexOf(itemId) === -1) {
                    this.schedule_list[agent_key]['departments'].push(itemId);
                }
            }
        },
        openCalendar(key, fieldName, event)
        {
            if (this.calendarObj) {
                if (fieldName !== 'time') {
                    var container = this.calendarObj.DIV;
                    if (container) {
                        if (BX.hasClass(container, 'v-time-selector')) {
                            BX.removeClass(container, 'v-time-selector');
                        }
                    }
                }
                this.calendarObj.Close();
                this.calendarObj = null;
            }

            var target = event.target;
            if (!this.schedule_list[key] || !target || !fieldName) {
                return;
            }
            var elemName = target.name;
            if (!elemName) {
                return;
            }
            var value = this.schedule_list[key][fieldName];
            if (!value) {
                value = this.default_schedule[fieldName];
            } else if (typeof value === 'string') {
                if (value.length === 0) {
                    value = this.default_schedule[fieldName];
                }
            }
            if (!value) {
                return;
            }
            if (fieldName === 'time') {
                value = BX.date.format('d.m.Y ') + value;
            }

            var obj = this;
            var params = {
                value: value,
                node: target,
                field: target,
                bTime: true,
                callback: function (date) {
                    if (!date) {
                        return false;
                    }
                    var time = BX.date.format('d.m.Y H:i:s', date);
                    if (fieldName === 'time') {
                        time = BX.date.format('H:i:s', date);
                    }
                    if (!time) {
                        return false;
                    }
                },
                callback_after: function (date) {
                    var elem = document.querySelector('input[name="'+elemName+'"]');
                    if (!date || !obj.schedule_list[key] || !elem) {
                        return false;
                    }
                    var time = BX.date.format('d.m.Y H:i:s', date);
                    if (fieldName === 'time') {
                        time = BX.date.format('H:i:s', date);
                    }
                    if (!time) {
                        return false;
                    }
                    obj.schedule_list[key][fieldName] = time;
                    elem.value = time;
                },
            };

            this.calendarObj = BX.calendar(params);
            if (!this.calendarObj) {
                return;
            }
            if (fieldName === 'time') {
                var calendarContainer = this.calendarObj.DIV;
                if (!calendarContainer) {
                    return;
                }
                if (!BX.hasClass(calendarContainer, 'v-time-selector')) {
                    BX.addClass(calendarContainer, 'v-time-selector');
                }
            }
        },
    },
    mounted() {
        this.default_schedule['departments'] = [];
        this.default_schedule['users'] = [];
    },
    template: `
<div class="adm-detail-content-wrap">
    <div class="top-buttons adm-detail-content-btns-wrap">
        <div class="adm-detail-content-btns">
            <slot v-for="(control,k) in controls">
                <input
                    :name="k"
                    type="button"
                    :value="control.name"
                    :class="{'adm-btn-save': control.type === 1, 'adm-btn-delete': control.type === 2}"
                    @click="handleControl(control.func)"
                />
            </slot>
        </div>
    </div>

    <div class="adm-detail-content">
        <slot v-for="(agent,key) in schedule_list" :key="agent.id">
            <div :data-id="key" class="agent-edit-block adm-detail-content-item-block">
            
                <div class="form-block">
                    <slot v-for="field in fields">
                        <div class="field-block">
                            <slot v-if="field['type'] === 'hidden'">
                                <input
                                    type="hidden"
                                    class="adm-input"
                                    :name="key+'_'+field.id"
                                    v-model="agent[field.id]"
                                />
                            </slot>
                            <slot v-else>
                                <div class="adm-detail-content-cell-l">
                                    <div class="agent-field">
                                        <slot v-if="field['required'] === true">
                                            <span class="field-required">*&nbsp;</span>
                                        </slot>
                                        {{field.title}}:
                                    </div>
                                </div>
                                <div class="adm-detail-content-cell-r">
                                    <div class="agent-field">
                                        <slot v-if="field.type === 'textarea'">
                                            <textarea
                                                class="adm-input"
                                                :name="key+'_'+field.id"
                                                v-model="agent[field.id]"
                                            ></textarea>
                                        </slot>
                                        <slot v-else-if="field.type === 'entity-selector'">
                                            <user-selector 
                                                :agent_key="key"
                                                :schedule="schedule_list[key]"
                                                :users="userNames"
                                                :departments="departmentNames"
                                                @onDeselectEntity="onDeselectEntity"
                                                @onSelectEntity="onSelectEntity"
                                            />
                                        </slot>
                                        <slot v-else>
                                            <slot v-if="field.id === 'time' || field.id === 'start_time'">
                                                <input
                                                    :type="field.type"
                                                    class="adm-input"
                                                    :class="{['w-'+field.width]: field.width}"
                                                    :name="key+'_'+field.id"
                                                    v-model="agent[field.id]"
                                                    @click="openCalendar(key, field.id, $event)"
                                                />
                                            </slot>
                                            <slot v-else>
                                                <input
                                                    :type="field.type"
                                                    class="adm-input"
                                                    :class="{['w-'+field.width]: field.width}"
                                                    :name="key+'_'+field.id"
                                                    v-model="agent[field.id]"
                                                    :min="[field.min]"
                                                    :step="[field.step]"
                                                />
                                            </slot>
                                        </slot>
                                    </div>
                                </div>
                            </slot>
                        </div>
                    </slot>
                </div>

                <div class="info-block">
                    <div class="messages-block">
                        <slot v-if="agent_list[key]">
                            <div>{{agent_messages.enabled}}</div>
                            <div>{{agent_messages.next_exec}}: {{agent_list[key]['NEXT_EXEC']}}</div>
                        </slot>
                        <slot v-else>
                            <div>{{agent_messages.disabled}}</div>
                        </slot>

                        <div>
                            <slot v-if="messages[key]">
                                <span :class="{'msg-red': messages[key].type === 'error', 'msg-green': messages[key].type === 'apply'}">
                                    {{messages[key].text}}
                                </span>
                            </slot>
                        </div>
                    </div>

                    <div class="buttons-block adm-detail-content-btns-wrap">
                        <div class="agent-btns adm-detail-content-btns">
                            <slot v-for="button in agent_buttons">
                                <input
                                    :name="key+'_'+button.func"
                                    type="button"
                                    :value="button.name"
                                    :class="{'adm-btn-save': button.type === 1, 'adm-btn-delete': button.type === 2}"
                                    @click="handleButton(button.func, key)"
                                />
                            </slot>
                        </div>
                    </div>
                </div>
                
            </div>
        </slot>
    </div>
</div>
    `,
});

Agentmessages.Settings = function (params)
{
    var data = BX.parseJSON(params);
    if (data) {
        if (data.schedule_list) {
            for (var i = 0; i < Object.keys(data.schedule_list).length; i++)
            {
                var elem = Object.values(data.schedule_list)[i];
                elem['message'] = BX.util.htmlspecialcharsback(elem['message']);
            }
        }
    } else {
        data = {settings: {}};
    }
    this.params = BX.parseJSON(data);
};
Agentmessages.Settings.prototype =
{
    initialize: function ()
    {
        var appContainer = document.getElementById('schedule-list-app');
        if (!appContainer) return;

        var scheduleList = BX.create('div', {
            attrs: {
                class: 'schedule-list',
            },
            html: '<schedule-list :params="params"/>',
        });
        BX.prepend(scheduleList, appContainer);

        BX.Vue.create({
            el: '#schedule-list-app',
            data: {
                params: this.params,
            },
        });
    },
};