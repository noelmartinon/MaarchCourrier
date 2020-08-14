import { Injectable } from '@angular/core';
import { LANG } from '../app/translate.component';
import { TranslateService } from '@ngx-translate/core';
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
            'label': this.translate.instant('lang.users'),
            'comment': this.translate.instant('lang.adminUsersDesc'),
            'route': '/administration/users',
            'unit': 'organisation',
            'style': 'fa fa-user',
            'angular': true,
            'hasParams': true
        },
        {
            'id': 'admin_groups',
            'label': this.translate.instant('lang.groups'),
            'comment': this.translate.instant('lang.adminGroupsDesc'),
            'route': '/administration/groups',
            'unit': 'organisation',
            'style': 'fa fa-users',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'manage_entities',
            'label': this.translate.instant('lang.entities'),
            'comment': this.translate.instant('lang.adminEntitiesDesc'),
            'route': '/administration/entities',
            'unit': 'organisation',
            'style': 'fa fa-sitemap',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_listmodels',
            'label': this.translate.instant('lang.workflowModels'),
            'comment': this.translate.instant('lang.adminWorkflowModelsDesc'),
            'route': '/administration/diffusionModels',
            'unit': 'organisation',
            'style': 'fa fa-th-list',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_architecture',
            'label': this.translate.instant('lang.documentTypes'),
            'comment': this.translate.instant('lang.adminDocumentTypesDesc'),
            'route': '/administration/doctypes',
            'unit': 'classement',
            'style': 'fa fa-suitcase',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_tag',
            'label': this.translate.instant('lang.tags'),
            'comment': this.translate.instant('lang.adminTagsDesc'),
            'route': '/administration/tags',
            'unit': 'classement',
            'style': 'fa fa-tags',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_baskets',
            'label': this.translate.instant('lang.baskets'),
            'comment': this.translate.instant('lang.adminBasketsDesc'),
            'route': '/administration/baskets',
            'unit': 'production',
            'style': 'fa fa-inbox',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_status',
            'label': this.translate.instant('lang.statuses'),
            'comment': this.translate.instant('lang.statusesAdmin'),
            'route': '/administration/statuses',
            'unit': 'production',
            'style': 'fa fa-check-circle',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_actions',
            'label': this.translate.instant('lang.actions'),
            'comment': this.translate.instant('lang.actionsAdmin'),
            'route': '/administration/actions',
            'unit': 'production',
            'style': 'fa fa-exchange-alt',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_contacts',
            'label': this.translate.instant('lang.contacts'),
            'comment': this.translate.instant('lang.contactsAdmin'),
            'route': '/administration/contacts',
            'unit': 'production',
            'style': 'fa fa-address-book',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_priorities',
            'label': this.translate.instant('lang.prioritiesAlt'),
            'comment': this.translate.instant('lang.prioritiesAlt'),
            'route': '/administration/priorities',
            'unit': 'production',
            'style': 'fa fa-clock',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_templates',
            'label': this.translate.instant('lang.templates'),
            'comment': this.translate.instant('lang.templatesAdmin'),
            'route': '/administration/templates',
            'unit': 'production',
            'style': 'fa fa-file-alt',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_indexing_models',
            'label': this.translate.instant('lang.indexingModels'),
            'comment': this.translate.instant('lang.indexingModels'),
            'route': '/administration/indexingModels',
            'unit': 'production',
            'style': 'fab fa-wpforms',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_custom_fields',
            'label': this.translate.instant('lang.customFieldsAdmin'),
            'comment': this.translate.instant('lang.customFieldsAdmin'),
            'route': '/administration/customFields',
            'unit': 'production',
            'style': 'fa fa-code',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_notif',
            'label': this.translate.instant('lang.notifications'),
            'comment': this.translate.instant('lang.notificationsAdmin'),
            'route': '/administration/notifications',
            'unit': 'production',
            'style': 'fa fa-bell',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'update_status_mail',
            'label': this.translate.instant('lang.updateStatus'),
            'comment': this.translate.instant('lang.updateStatus'),
            'route': '/administration/update-status',
            'unit': 'supervision',
            'style': 'fa fa-envelope-square',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_docservers',
            'label': this.translate.instant('lang.docservers'),
            'comment': this.translate.instant('lang.docserversAdmin'),
            'route': '/administration/docservers',
            'unit': 'supervision',
            'style': 'fa fa-hdd',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_parameters',
            'label': this.translate.instant('lang.parameters'),
            'comment': this.translate.instant('lang.parameters'),
            'route': '/administration/parameters',
            'unit': 'supervision',
            'style': 'fa fa-wrench',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_password_rules',
            'label': this.translate.instant('lang.securities'),
            'comment': this.translate.instant('lang.securities'),
            'route': '/administration/securities',
            'unit': 'supervision',
            'style': 'fa fa-lock',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_email_server',
            'label': this.translate.instant('lang.emailServerParam'),
            'comment': this.translate.instant('lang.emailServerParamDesc'),
            'route': '/administration/sendmail',
            'unit': 'supervision',
            'style': 'fa fa-mail-bulk',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_shippings',
            'label': this.translate.instant('lang.mailevaAdmin'),
            'comment': this.translate.instant('lang.mailevaAdminDesc'),
            'route': '/administration/shippings',
            'unit': 'supervision',
            'style': 'fa fa-shipping-fast',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'view_history',
            'label': this.translate.instant('lang.history'),
            'comment': this.translate.instant('lang.viewHistoryDesc'),
            'route': '/administration/history',
            'unit': 'supervision',
            'style': 'fa fa-history',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'view_history_batch',
            'label': this.translate.instant('lang.historyBatch'),
            'comment': this.translate.instant('lang.historyBatchAdmin'),
            'route': '/administration/history-batch',
            'unit': 'supervision',
            'style': 'fa fa-history',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_update_control',
            'label': this.translate.instant('lang.updateControl'),
            'comment': this.translate.instant('lang.updateControlDesc'),
            'route': '/administration/versions-update',
            'unit': 'supervision',
            'style': 'fa fa-sync',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_alfresco',
            'label': this.translate.instant('lang.alfresco'),
            'comment': this.translate.instant('lang.adminAlfrescoDesc'),
            'route': '/administration/alfresco',
            'unit': 'supervision',
            'style': 'alfresco',
            'angular': true,
            'hasParams': false
        },
        {
            'id': 'admin_registered_mail',
            'label': this.translate.instant('lang.registeredMail'),
            'comment': this.translate.instant('lang.adminRegisteredMailDesc'),
            'route': '/administration/registeredMails',
            'unit': 'supervision',
            'style': 'fas fa-dolly-flatbed',
            'angular': true,
            'hasParams': false
        }
    ];

    private privileges: privilege[] = [
        {
            'id': 'view_doc_history',
            'label': this.translate.instant('lang.viewDocHistory'),
            'comment': this.translate.instant('lang.viewHistoryDesc'),
            'unit': 'history'
        },
        {
            'id': 'view_full_history',
            'label': this.translate.instant('lang.viewFullHistory'),
            'comment': this.translate.instant('lang.viewFullHistoryDesc'),
            'unit': 'history'
        },
        {
            'id': 'edit_resource',
            'label': this.translate.instant('lang.editResource'),
            'comment': this.translate.instant('lang.editResourceDesc'),
            'unit': 'application'
        },
        {
            'id': 'add_links',
            'label': this.translate.instant('lang.addLinks'),
            'comment': this.translate.instant('lang.addLinks'),
            'unit': 'application'
        },
        {
            'id': 'manage_tags_application',
            'label': this.translate.instant('lang.manageTagsInApplication'),
            'comment': this.translate.instant('lang.manageTagsInApplicationDesc'),
            'unit': 'application'
        },
        {
            'id': 'create_contacts',
            'label': this.translate.instant('lang.manageCreateContacts'),
            'comment': this.translate.instant('lang.manageCreateContactsDesc'),
            'unit': 'application'
        },
        {
            'id': 'update_contacts',
            'label': this.translate.instant('lang.manageUpdateContacts'),
            'comment': this.translate.instant('lang.manageUpdateContactsDesc'),
            'unit': 'application'
        },
        {
            'id': 'update_diffusion_indexing',
            'label': this.translate.instant('lang.allRoles'),
            'comment': this.translate.instant('lang.updateDiffusionWhileIndexing'),
            'unit': 'diffusionList'
        },
        {
            'id': 'update_diffusion_except_recipient_indexing',
            'label': this.translate.instant('lang.rolesExceptAssignee'),
            'comment': this.translate.instant('lang.updateDiffusionExceptRecipientWhileIndexing'),
            'unit': 'diffusionList'
        },
        {
            'id': 'update_diffusion_process',
            'label': this.translate.instant('lang.allRoles'),
            'comment': this.translate.instant('lang.updateDiffusionWhileProcess'),
            'unit': 'diffusionList'
        },
        {
            'id': 'update_diffusion_except_recipient_process',
            'label': this.translate.instant('lang.rolesExceptAssignee'),
            'comment': this.translate.instant('lang.updateDiffusionExceptRecipientWhileProcess'),
            'unit': 'diffusionList'
        },
        {
            'id': 'update_diffusion_details',
            'label': this.translate.instant('lang.allRoles'),
            'comment': this.translate.instant('lang.updateDiffusionWhileDetails'),
            'unit': 'diffusionList'
        },
        {
            'id': 'update_diffusion_except_recipient_details',
            'label': this.translate.instant('lang.rolesExceptAssignee'),
            'comment': this.translate.instant('lang.updateDiffusionExceptRecipientWhileDetails'),
            'unit': 'diffusionList'
        },
        {
            'id': 'sendmail',
            'label': this.translate.instant('lang.sendmail'),
            'comment': this.translate.instant('lang.sendmail'),
            'unit': 'sendmail'
        },
        {
            'id': 'use_mail_services',
            'label': this.translate.instant('lang.useMailServices'),
            'comment': this.translate.instant('lang.useMailServices'),
            'unit': 'sendmail'
        },
        {
            'id': 'view_documents_with_notes',
            'label': this.translate.instant('lang.viewDocumentsWithNotes'),
            'comment': this.translate.instant('lang.viewDocumentsWithNotesDesc'),
            'unit': 'application'
        },
        {
            'id': 'view_technical_infos',
            'label': this.translate.instant('lang.viewTechnicalInformation'),
            'comment': this.translate.instant('lang.viewTechnicalInformation'),
            'unit': 'application'
        },
        {
            'id': 'config_avis_workflow',
            'label': this.translate.instant('lang.configAvisWorkflow'),
            'comment': this.translate.instant('lang.configAvisWorkflowDesc'),
            'unit': 'avis'
        },
        {
            'id': 'config_avis_workflow_in_detail',
            'label': this.translate.instant('lang.configAvisWorkflowInDetail'),
            'comment': this.translate.instant('lang.configAvisWorkflowInDetailDesc'),
            'unit': 'avis'
        },
        {
            'id': 'avis_documents',
            'label': this.translate.instant('lang.avisAnswer'),
            'comment': this.translate.instant('lang.avisAnswerDesc'),
            'unit': 'avis'
        },
        {
            'id': 'config_visa_workflow',
            'label': this.translate.instant('lang.configVisaWorkflow'),
            'comment': this.translate.instant('lang.configVisaWorkflowDesc'),
            'unit': 'visaWorkflow'
        },
        {
            'id': 'config_visa_workflow_in_detail',
            'label': this.translate.instant('lang.configVisaWorkflowInDetail'),
            'comment': this.translate.instant('lang.configVisaWorkflowInDetailDesc'),
            'unit': 'visaWorkflow'
        },
        {
            'id': 'visa_documents',
            'label': this.translate.instant('lang.visaAnswers'),
            'comment': this.translate.instant('lang.visaAnswersDesc'),
            'unit': 'visaWorkflow'
        },
        {
            'id': 'sign_document',
            'label': this.translate.instant('lang.signDocs'),
            'comment': this.translate.instant('lang.signDocs'),
            'unit': 'visaWorkflow'
        },
        {
            'id': 'modify_visa_in_signatureBook',
            'label': this.translate.instant('lang.modifyVisaInSignatureBook'),
            'comment': this.translate.instant('lang.modifyVisaInSignatureBookDesc'),
            'unit': 'visaWorkflow'
        },
        {
            'id': 'print_folder_doc',
            'label': this.translate.instant('lang.printFolderDoc'),
            'comment': this.translate.instant('lang.printFolderDoc'),
            'unit': 'application'
        },
        {
            'id': 'manage_attachments',
            'label': this.translate.instant('lang.manageAttachments'),
            'comment': this.translate.instant('lang.manageAttachments'),
            'unit': 'application'
        },
        {
            'id': 'view_personal_data',
            'label': this.translate.instant('lang.viewPersonalData'),
            'comment': this.translate.instant('lang.viewPersonalData'),
            'unit': 'confidentialityAndSecurity'
        },
        {
            'id': 'manage_personal_data',
            'label': this.translate.instant('lang.managePersonalData'),
            'comment': this.translate.instant('lang.managePersonalData'),
            'unit': 'confidentialityAndSecurity'
        },
        {
            'id': 'include_folders_and_followed_resources_perimeter',
            'label': this.translate.instant('lang.includeFolderPerimeter'),
            'comment': this.translate.instant('lang.includeFolderPerimeter'),
            'unit': 'application'
        },
    ];

    private menus: menu[] = [
        {
            'id': 'admin',
            'label': this.translate.instant('lang.administration'),
            'comment': this.translate.instant('lang.administration'),
            'route': '/administration',
            'style': 'fa fa-cogs',
            'unit': 'application',
            'angular': true,
            'shortcut': true
        },
        {
            'id': 'adv_search_mlb',
            'label': this.translate.instant('lang.search'),
            'comment': this.translate.instant('lang.search'),
            'route': 'index.php?page=search_adv&dir=indexing_searching',
            'style': 'fa fa-search',
            'unit': 'application',
            'angular': false,
            'shortcut': true
        },
        {
            'id': 'entities_print_sep_mlb',
            'label': this.translate.instant('lang.entitiesSeparator'),
            'comment': this.translate.instant('lang.entitiesSeparator'),
            'route': '/separators/print',
            'style': 'fa fa-print',
            'unit': 'entities',
            'angular': true,
            'shortcut': false
        },
        {
            'id': 'manage_numeric_package',
            'label': this.translate.instant('lang.manageNumericPackage'),
            'comment': this.translate.instant('lang.manageNumericPackage'),
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
            'label': this.translate.instant('lang.followedMail'),
            'comment': this.translate.instant('lang.followedMail'),
            'route': '/followed',
            'style': 'fas fa-star',
            'unit': 'application',
            'angular': true,
            'shortcut': true
        }
    ];

    constructor(public translate: TranslateService, public headerService: HeaderService) { }

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
                'label': this.translate.instant('lang.recordMail'),
                'comment': this.translate.instant('lang.recordMail'),
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
                'label': this.translate.instant('lang.followedMail'),
                'comment': this.translate.instant('lang.followedMail'),
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
                'label': this.translate.instant('lang.recordMail'),
                'comment': this.translate.instant('lang.recordMail'),
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
