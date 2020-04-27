import { Injectable } from '@angular/core';
import { LANG } from '../app/translate.component';
import { HeaderService } from './header.service';

interface menu {
    'id': string; // identifier
    'label': string; // title
    'comment': string; // description
    'route': string; // navigate to interface
    'style': string; // icon used interface
    'unit': string; // category of administration
    'angular': boolean; // to navigate in V1 <=>V2
    'shortcut': boolean; // show in panel
}

interface administration {
    'id': string; // identifier
    'label': string; // title
    'comment': string; // description
    'route': string; // navigate to interface
    'style': string; // icone used interface
    'unit': 'organisation' | 'classement' | 'production' | 'supervision'; // category of administration
    'angular': boolean; // to navigate in V1 <=>V2
    'hasParams': boolean;
}

interface privilege {
    'id': string; // identifier
    'label': string; // title
    'unit': string; // category of administration
    'comment': string; // description
}

@Injectable()
export class PrivilegeService {

    lang: any = LANG;

    private administrations: administration[] = [
        {
            'id': 'admin_users',
            'label': this.lang.users,
            'comment': this.lang.adminUsersDesc,
            'route': '/administration/users',
            'unit': 'organisation',
            'style': 'fa fa-user',
            'angular': true,
            'hasParams': true
        },
        {
            'id': 'admin_groups',
            'label': this.lang.groups,
            'comment': this.lang.adminGroupsDesc,
            'route': '/administration/groups',
            'unit': 'organisation',
            'style': 'fa fa-users',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'manage_entities',
            'label': this.lang.entities,
            'comment': this.lang.adminEntitiesDesc,
            'route': '/administration/entities',
            'unit': 'organisation',
            'style': 'fa fa-sitemap',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_listmodels',
            'label': this.lang.workflowModels,
            'comment': this.lang.adminWorkflowModelsDesc,
            'route': '/administration/diffusionModels',
            'unit': 'organisation',
            'style': 'fa fa-th-list',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_architecture',
            'label': this.lang.documentTypes,
            'comment': this.lang.adminDocumentTypesDesc,
            'route': '/administration/doctypes',
            'unit': 'classement',
            'style': 'fa fa-suitcase',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_tag',
            'label': this.lang.tags,
            'comment': this.lang.adminTagsDesc,
            'route': '/administration/tags',
            'unit': 'classement',
            'style': 'fa fa-tags',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_baskets',
            'label': this.lang.baskets,
            'comment': this.lang.adminBasketsDesc,
            'route': '/administration/baskets',
            'unit': 'production',
            'style': 'fa fa-inbox',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_status',
            'label': this.lang.statuses,
            'comment': this.lang.statusesAdmin,
            'route': '/administration/statuses',
            'unit': 'production',
            'style': 'fa fa-check-circle',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_actions',
            'label': this.lang.actions,
            'comment': this.lang.actionsAdmin,
            'route': '/administration/actions',
            'unit': 'production',
            'style': 'fa fa-exchange-alt',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_contacts',
            'label': this.lang.contacts,
            'comment': this.lang.contactsAdmin,
            'route': '/administration/contacts',
            'unit': 'production',
            'style': 'fa fa-address-book',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_priorities',
            'label': this.lang.prioritiesAlt,
            'comment': this.lang.prioritiesAlt,
            'route': '/administration/priorities',
            'unit': 'production',
            'style': 'fa fa-clock',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_templates',
            'label': this.lang.templates,
            'comment': this.lang.templatesAdmin,
            'route': '/administration/templates',
            'unit': 'production',
            'style': 'fa fa-file-alt',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_indexing_models',
            'label': this.lang.indexingModels,
            'comment': this.lang.indexingModels,
            'route': '/administration/indexingModels',
            'unit': 'production',
            'style': 'fab fa-wpforms',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_custom_fields',
            'label': this.lang.customFieldsAdmin,
            'comment': this.lang.customFieldsAdmin,
            'route': '/administration/customFields',
            'unit': 'production',
            'style': 'fa fa-code',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_notif',
            'label': this.lang.notifications,
            'comment': this.lang.notificationsAdmin,
            'route': '/administration/notifications',
            'unit': 'production',
            'style': 'fa fa-bell',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'update_status_mail',
            'label': this.lang.updateStatus,
            'comment': this.lang.updateStatus,
            'route': '/administration/update-status',
            'unit': 'supervision',
            'style': 'fa fa-envelope-square',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_docservers',
            'label': this.lang.docservers,
            'comment': this.lang.docserversAdmin,
            'route': '/administration/docservers',
            'unit': 'supervision',
            'style': 'fa fa-hdd',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_parameters',
            'label': this.lang.parameters,
            'comment': this.lang.parameters,
            'route': '/administration/parameters',
            'unit': 'supervision',
            'style': 'fa fa-wrench',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_password_rules',
            'label': this.lang.securities,
            'comment': this.lang.securities,
            'route': '/administration/securities',
            'unit': 'supervision',
            'style': 'fa fa-lock',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_email_server',
            'label': this.lang.emailServerParam,
            'comment': this.lang.emailServerParamDesc,
            'route': '/administration/sendmail',
            'unit': 'supervision',
            'style': 'fa fa-mail-bulk',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_shippings',
            'label': this.lang.mailevaAdmin,
            'comment': this.lang.mailevaAdminDesc,
            'route': '/administration/shippings',
            'unit': 'supervision',
            'style': 'fa fa-shipping-fast',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'view_history',
            'label': this.lang.history,
            'comment': this.lang.viewHistoryDesc,
            'route': '/administration/history',
            'unit': 'supervision',
            'style': 'fa fa-history',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'view_history_batch',
            'label': this.lang.historyBatch,
            'comment': this.lang.historyBatchAdmin,
            'route': '/administration/history-batch',
            'unit': 'supervision',
            'style': 'fa fa-history',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_update_control',
            'label': this.lang.updateControl,
            'comment': this.lang.updateControlDesc,
            'route': '/administration/versions-update',
            'unit': 'supervision',
            'style': 'fa fa-sync',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_alfresco',
            'label': this.lang.alfresco,
            'comment': this.lang.adminAlfrescoDesc,
            'route': '/administration/alfresco',
            'unit': 'supervision',
            'style': 'alfresco',
            'angular': true,
            'hasParams': false
        }
    ];

    private privileges: privilege[] = [
        {
            'id': 'view_doc_history',
            'label': this.lang.viewDocHistory,
            'comment': this.lang.viewHistoryDesc,
            'unit': 'history'
        },
        {
            'id': 'view_full_history',
            'label': this.lang.viewFullHistory,
            'comment': this.lang.viewFullHistoryDesc,
            'unit': 'history'
        },
        {
            'id': 'edit_resource',
            'label': this.lang.editResource,
            'comment': this.lang.editResourceDesc,
            'unit': 'application'
        },
        {
            'id': 'add_links',
            'label': this.lang.addLinks,
            'comment': this.lang.addLinks,
            'unit': 'application'
        },
        {
            'id': 'manage_tags_application',
            'label': this.lang.manageTagsInApplication,
            'comment': this.lang.manageTagsInApplicationDesc,
            'unit': 'application'
        },
        {
            'id': 'create_contacts',
            'label': this.lang.manageCreateContacts,
            'comment': this.lang.manageCreateContactsDesc,
            'unit': 'application'
        },
        {
            'id': 'update_contacts',
            'label': this.lang.manageUpdateContacts,
            'comment': this.lang.manageUpdateContactsDesc,
            'unit': 'application'
        },
        {
            'id': 'update_diffusion_indexing',
            'label': this.lang.allRoles,
            'comment': this.lang.updateDiffusionWhileIndexing,
            'unit': 'diffusionList'
        },
        {
            'id': 'update_diffusion_except_recipient_indexing',
            'label': this.lang.rolesExceptAssignee,
            'comment': this.lang.updateDiffusionExceptRecipientWhileIndexing,
            'unit': 'diffusionList'
        },
        {
            'id': 'update_diffusion_process',
            'label': this.lang.allRoles,
            'comment': this.lang.updateDiffusionWhileProcess,
            'unit': 'diffusionList'
        },
        {
            'id': 'update_diffusion_except_recipient_process',
            'label': this.lang.rolesExceptAssignee,
            'comment': this.lang.updateDiffusionExceptRecipientWhileProcess,
            'unit': 'diffusionList'
        },
        {
            'id': 'update_diffusion_details',
            'label': this.lang.allRoles,
            'comment': this.lang.updateDiffusionWhileDetails,
            'unit': 'diffusionList'
        },
        {
            'id': 'update_diffusion_except_recipient_details',
            'label': this.lang.rolesExceptAssignee,
            'comment': this.lang.updateDiffusionExceptRecipientWhileDetails,
            'unit': 'diffusionList'
        },
        {
            'id': 'sendmail',
            'label': this.lang.sendmail,
            'comment': this.lang.sendmail,
            'unit': 'sendmail'
        },
        {
            'id': 'use_mail_services',
            'label': this.lang.useMailServices,
            'comment': this.lang.useMailServices,
            'unit': 'sendmail'
        },
        {
            'id': 'view_documents_with_notes',
            'label': this.lang.viewDocumentsWithNotes,
            'comment': this.lang.viewDocumentsWithNotesDesc,
            'unit': 'application'
        },
        {
            'id': 'view_technical_infos',
            'label': this.lang.viewTechnicalInformation,
            'comment': this.lang.viewTechnicalInformation,
            'unit': 'application'
        },
        {
            'id': 'config_avis_workflow',
            'label': this.lang.configAvisWorkflow,
            'comment': this.lang.configAvisWorkflowDesc,
            'unit': 'avis'
        },
        {
            'id': 'config_avis_workflow_in_detail',
            'label': this.lang.configAvisWorkflowInDetail,
            'comment': this.lang.configAvisWorkflowInDetailDesc,
            'unit': 'avis'
        },
        {
            'id': 'avis_documents',
            'label': this.lang.avisAnswer,
            'comment': this.lang.avisAnswerDesc,
            'unit': 'avis'
        },
        {
            'id': 'config_visa_workflow',
            'label': this.lang.configVisaWorkflow,
            'comment': this.lang.configVisaWorkflowDesc,
            'unit': 'visaWorkflow'
        },
        {
            'id': 'config_visa_workflow_in_detail',
            'label': this.lang.configVisaWorkflowInDetail,
            'comment': this.lang.configVisaWorkflowInDetailDesc,
            'unit': 'visaWorkflow'
        },
        {
            'id': 'visa_documents',
            'label': this.lang.visaAnswers,
            'comment': this.lang.visaAnswersDesc,
            'unit': 'visaWorkflow'
        },
        {
            'id': 'sign_document',
            'label': this.lang.signDocs,
            'comment': this.lang.signDocs,
            'unit': 'visaWorkflow'
        },
        {
            'id': 'modify_visa_in_signatureBook',
            'label': this.lang.modifyVisaInSignatureBook,
            'comment': this.lang.modifyVisaInSignatureBookDesc,
            'unit': 'visaWorkflow'
        },
        {
            'id': 'print_folder_doc',
            'label': this.lang.printFolderDoc,
            'comment': this.lang.printFolderDoc,
            'unit': 'application'
        },
        {
            'id': 'manage_attachments',
            'label': this.lang.manageAttachments,
            'comment': this.lang.manageAttachments,
            'unit': 'application'
        },
        {
            'id': 'view_personal_data',
            'label': this.lang.viewPersonalData,
            'comment': this.lang.viewPersonalData,
            'unit': 'confidentialityAndSecurity'
        },
        {
            'id': 'manage_personal_data',
            'label': this.lang.managePersonalData,
            'comment': this.lang.managePersonalData,
            'unit': 'confidentialityAndSecurity'
        },
    ];

    private menus: menu[] = [
        {
            'id': 'admin',
            'label': this.lang.administration,
            'comment': this.lang.administration,
            'route': '/administration',
            'style': 'fa fa-cogs',
            'unit': 'application',
            'angular': true,
            'shortcut': true
        },
        {
            'id': 'adv_search_mlb',
            'label': this.lang.search,
            'comment': this.lang.search,
            'route': 'index.php?page=search_adv&dir=indexing_searching',
            'style': 'fa fa-search',
            'unit': 'application',
            'angular': false,
            'shortcut': true
        },
        {
            'id': 'entities_print_sep_mlb',
            'label': this.lang.entitiesSeparator,
            'comment': this.lang.entitiesSeparator,
            'route': '/separators/print',
            'style': 'fa fa-print',
            'unit': 'entities',
            'angular': true,
            'shortcut': false
        },
        {
            'id': 'manage_numeric_package',
            'label': this.lang.manageNumericPackage,
            'comment': this.lang.manageNumericPackage,
            'route': '/saveNumericPackage',
            'style': 'fa fa-file-archive',
            'unit': 'sendmail',
            'angular': true,
            'shortcut': false
        }
    ];

    shortcuts: any[] = [
        {
            'id': 'followed',
            'label': this.lang.followedMail,
            'comment': this.lang.followedMail,
            'route': '/followed',
            'style': 'fas fa-star',
            'unit': 'application',
            'angular': true,
            'shortcut': true
        }
    ];

    constructor(public headerService: HeaderService) { }

    getAllPrivileges() {
        let priv: any[] = [];

        priv = priv.concat(this.privileges.map(elem => elem.id));
        priv = priv.concat(this.administrations.map(elem => elem.id));
        priv = priv.concat(this.menus.map(elem => elem.id));

        return priv;
    }

    getPrivileges(ids: string[] = null) {
        if (ids !== null) {
            return this.privileges.filter(elem => ids.indexOf(elem.id) > -1);
        } else {
            return this.privileges;
        }

    }

    getUnitsPrivileges(): Array<string> {
        return this.privileges.map(elem => elem.unit).filter((elem, pos, arr) => arr.indexOf(elem) === pos);
    }


    getPrivilegesByUnit(unit: string): Array<privilege> {
        return this.privileges.filter(elem => elem.unit === unit);
    }

    getMenus(): Array<menu> {
        return this.menus;
    }

    getCurrentUserMenus() {
        let menus = this.menus.filter(elem => this.headerService.user.privileges.indexOf(elem.id) > -1);

        if (this.headerService.user.groups.filter((group: any) => group.can_index === true).length > 0) {
            const indexingGroups: any[] = [];

            this.headerService.user.groups.filter((group: any) => group.can_index === true).forEach((group: any) => {
                indexingGroups.push({
                    id: group.id,
                    label: group.group_desc
                });
            });

            const indexingmenu: any = {
                'id': 'indexing',
                'label': this.lang.recordMail,
                'comment': this.lang.recordMail,
                'route': '/indexing/' + indexingGroups[0].id,
                'style': 'fa fa-file-medical',
                'unit': 'application',
                'angular': true,
                'shortcut': true,
                'groups': indexingGroups
            };
            menus.push(indexingmenu);
        }

        return menus;
    }

    getMenusByUnit(unit: string): Array<menu> {
        return this.menus.filter(elem => elem.unit === unit);
    }

    getUnitsMenus(): Array<string> {
        return this.menus.map(elem => elem.unit).filter((elem, pos, arr) => arr.indexOf(elem) === pos);
    }

    resfreshUserShortcuts() {
        this.shortcuts = [
            {
                'id': 'followed',
                'label': this.lang.followedMail,
                'comment': this.lang.followedMail,
                'route': '/followed',
                'style': 'fas fa-star',
                'unit': 'application',
                'angular': true,
                'shortcut': true
            }
        ];

        this.shortcuts = this.shortcuts.concat(this.menus.filter(elem => elem.shortcut === true).filter(elem => this.headerService.user.privileges.indexOf(elem.id) > -1));

        if (this.headerService.user.groups.filter((group: any) => group.can_index === true).length > 0) {
            const indexingGroups: any[] = [];

            this.headerService.user.groups.filter((group: any) => group.can_index === true).forEach((group: any) => {
                indexingGroups.push({
                    id: group.id,
                    label: group.group_desc
                });
            });

            const indexingShortcut: any = {
                'id': 'indexing',
                'label': this.lang.recordMail,
                'comment': this.lang.recordMail,
                'route': '/indexing',
                'style': 'fa fa-file-medical',
                'unit': 'application',
                'angular': true,
                'shortcut': true,
                'groups': indexingGroups
            };
            this.shortcuts.push(indexingShortcut);
        }
    }

    getAdministrations(): Array<administration> {
        return this.administrations;
    }

    getCurrentUserAdministrationsByUnit(unit: string): Array<administration> {
        if (this.hasCurrentUserPrivilege('view_history') && this.hasCurrentUserPrivilege('view_history_batch')) {
            return this.administrations.filter(elem => elem.unit === unit).filter(elem => this.headerService.user.privileges.indexOf(elem.id) > -1).filter(priv => priv.id !== 'view_history_batch');
        } else {
            return this.administrations.filter(elem => elem.unit === unit).filter(elem => this.headerService.user.privileges.indexOf(elem.id) > -1);
        }

    }

    hasCurrentUserPrivilege(privilegeId: string) {

        return this.headerService.user.privileges.indexOf(privilegeId) > -1;
    }
}
