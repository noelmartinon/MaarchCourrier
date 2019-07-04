import { ChangeDetectorRef, Component, OnInit, NgZone, ViewChild, Inject } from '@angular/core';
import { MediaMatcher } from '@angular/cdk/layout';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';
import { LANG } from '../../translate.component';
import { MatSidenav, MatPaginator, MatTableDataSource, MatSort, MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material';
import { FormControl, FormGroup, Validators, AbstractControl, ValidationErrors, ValidatorFn, FormBuilder } from '@angular/forms';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';

import { AutoCompletePlugin } from '../../../plugins/autocomplete.plugin';
import { SelectionModel } from '@angular/cdk/collections';
import { AccountLinkComponent } from './account-link/account-link.component';

declare function $j(selector: any): any;
declare const angularGlobals: any;


@Component({
    templateUrl: "user-administration.component.html",
    styleUrls: ['user-administration.component.scss'],
    providers: [NotificationService]
})
export class UserAdministrationComponent extends AutoCompletePlugin implements OnInit {
    @ViewChild('snav') public sidenavLeft: MatSidenav;
    @ViewChild('snav2') public sidenavRight: MatSidenav;


    private _mobileQueryListener: () => void;
    mobileQuery: MediaQueryList;

    coreUrl: string;
    lang: any = LANG;
    loading: boolean = false;
    dialogRef: MatDialogRef<any>;
    config: any = {};
    serialId: number;
    userId: string;
    mode: string = '';
    user: any = {};
    _search: string = '';
    creationMode: boolean;

    signatureModel: any = {
        base64: "",
        base64ForJs: "",
        name: "",
        type: "",
        size: 0,
        label: "",
    };
    userAbsenceModel: any[] = [];
    userList: any[] = [];
    maarchParapheurLink: any = {
        login: '',
        picture: ''
    }
    selectedSignature: number = -1;
    selectedSignatureLabel: string = "";
    loadingSign: boolean = false;
    data: any[] = [];
    CurrentYear: number = new Date().getFullYear();
    currentMonth: number = new Date().getMonth() + 1;
    minDate: Date = new Date();
    firstFormGroup: FormGroup;
    ruleText: string = '';
    otherRuleText: string;
    validPassword: boolean = false;
    showPassword: boolean = false;
    hidePassword: boolean = true;
    passwordModel: any = {
        currentPassword: "",
        newPassword: "",
        reNewPassword: ""
    };
    passwordRules: any = {
        minLength: { enabled: false, value: 0 },
        complexityUpper: { enabled: false, value: 0 },
        complexityNumber: { enabled: false, value: 0 },
        complexitySpecial: { enabled: false, value: 0 },
        renewal: { enabled: false, value: 0 },
        historyLastUse: { enabled: false, value: 0 }
    };

    displayedColumns = ['event_date', 'event_type', 'info', 'remote_ip'];
    dataSource = new MatTableDataSource(this.data);
    selectedTabIndex: number = 0;
    maarchParapheurConnectionStatus = true;

    @ViewChild(MatPaginator) paginator: MatPaginator;
    @ViewChild(MatSort) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim();
        filterValue = filterValue.toLowerCase();
        this.dataSource.filter = filterValue;
    }

    //Redirect Baskets
    selectionBaskets = new SelectionModel<Element>(true, []);
    masterToggleBaskets(event: any) {
        if (event.checked) {
            this.user.baskets.forEach((basket: any) => {
                if (!basket.userToDisplay) {
                    this.selectionBaskets.select(basket);
                }
            });
        } else {
            this.selectionBaskets.clear();
        }
    }


    constructor(changeDetectorRef: ChangeDetectorRef, media: MediaMatcher, public http: HttpClient, private route: ActivatedRoute, private router: Router, private zone: NgZone, private notify: NotificationService, public dialog: MatDialog, private headerService: HeaderService, private _formBuilder: FormBuilder) {
        super(http, ['users']);
        $j("link[href='merged_css.php']").remove();
        this.mobileQuery = media.matchMedia('(max-width: 768px)');
        this._mobileQueryListener = () => changeDetectorRef.detectChanges();
        this.mobileQuery.addListener(this._mobileQueryListener);
        window['angularUserAdministrationComponent'] = {
            componentAfterUpload: (base64Content: any) => this.processAfterUpload(base64Content),
        };
    }

    ngOnDestroy(): void {
        this.mobileQuery.removeListener(this._mobileQueryListener);
    }

    ngOnInit(): void {

        this.coreUrl = angularGlobals.coreUrl;

        this.loading = true;

        this.route.params.subscribe((params: any) => {
            if (typeof params['id'] == "undefined") {
                window['MainHeaderComponent'].setSnav(this.sidenavLeft);
                window['MainHeaderComponent'].setSnavRight(null);

                this.headerService.setHeader(this.lang.userCreation);
                this.creationMode = true;
                this.loading = false;
            } else {
                window['MainHeaderComponent'].setSnav(this.sidenavLeft);
                window['MainHeaderComponent'].setSnavRight(this.sidenavRight);

                this.creationMode = false;
                this.serialId = params['id'];
                this.http.get(this.coreUrl + "rest/users/" + this.serialId + "/details")
                    .subscribe((data: any) => {
                        this.user = data;

                        this.data = data.history;
                        this.userId = data.user_id;
                        this.minDate = new Date(this.CurrentYear + '-' + this.currentMonth + '-01');
                        this.headerService.setHeader(this.lang.userModification, data.firstname + " " + data.lastname);

                        if (this.user.external_id.maarchParapheur !== undefined) {
                            this.checkInfoMaarchParapheurAccount();
                        }

                        this.loading = false;
                        setTimeout(() => {
                            this.dataSource = new MatTableDataSource(this.data);
                            this.dataSource.paginator = this.paginator;
                            this.dataSource.sort = this.sort;
                        }, 0);
                    }, () => {
                        location.href = "index.php";
                    });
            }
        });
    }

    checkInfoMaarchParapheurAccount() {
        this.http.get("../../rest/users/" + this.serialId + "/statusInMaarchParapheur")
            .subscribe((data: any) => {
                this.maarchParapheurLink.login = data.link;
                this.loading = false;
                if (this.maarchParapheurLink.login !== '') {
                    this.loadAvatarMaarchParapheur(this.user.external_id.maarchParapheur);
                } else {
                    this.maarchParapheurConnectionStatus = false;
                }
            });
    }

    linkMaarchParapheurAccount() {
        const dialogRef = this.dialog.open(AccountLinkComponent, { autoFocus: false, data: { user: this.user } });
        dialogRef.afterClosed().subscribe(result => {
            if (result) {
                if (result.inMaarchParapheur) {
                    this.linkAccountToMaarchParahpeur(result.id);
                } else {
                    this.createAccountToMaarchParahpeur(result.id, result.login);
                }

            }
        });
    }

    linkAccountToMaarchParahpeur(externalId: number) {
        this.http.put(this.coreUrl + "rest/users/" + this.serialId + "/linkToMaarchParapheur", { maarchParapheurUserId: externalId })
            .subscribe(() => {
                this.user.canCreateMaarchParapheurUser = false;
                this.user.external_id['maarchParapheur'] = externalId;
                this.checkInfoMaarchParapheurAccount();
                this.notify.success(this.lang.accountLinked);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    createAccountToMaarchParahpeur(id: number, login: string) {
        this.http.put(this.coreUrl + "rest/users/" + id + "/createInMaarchParapheur", { login: login })
            .subscribe((data: any) => {
                this.user.canCreateMaarchParapheurUser = false;
                this.user.external_id['maarchParapheur'] = data.externalId;
                this.checkInfoMaarchParapheurAccount();
                this.notify.success(this.lang.accountAdded);
            }, (err) => {
                if (err.error.errors == 'Login already exists') {
                    err.error.errors = this.lang.loginAlreadyExistsInMaarchParapheur
                }
                this.notify.error(err.error.errors);
            });
    }

    loadAvatarMaarchParapheur(externalId: number) {
        this.http.get("../../rest/maarchParapheur/user/" + externalId + "/picture")
            .subscribe((data: any) => {
                this.maarchParapheurLink.picture = data.picture;

            }, (err) => {
                this.notify.handleErrors(err);
            });
    }

    unlinkMaarchParapheurAccount() {
        let r = confirm(this.lang.confirmAction + ' ' + this.lang.unlinkAccount);

        if (r) {
            this.http.put(this.coreUrl + "rest/users/" + this.serialId + "/unlinkToMaarchParapheur", {})
                .subscribe(() => {
                    this.user.canCreateMaarchParapheurUser = true;
                    this.maarchParapheurLink.login = '';
                    this.maarchParapheurLink.picture = '';
                    this.notify.success(this.lang.accountUnlinked);
                    this.maarchParapheurConnectionStatus = true;
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    toogleRedirect(basket: any) {
        $j('#redirectUser_' + basket.group_id + '_' + basket.basket_id).toggle();

        this.http.get(this.coreUrl + 'rest/users')
            .subscribe((data: any) => {
                //this.userList = data['users'];

            }, () => {
                location.href = "index.php";
            });
    }

    initService() {
        if ($j('.jstree-container-ul').length == 0) {
            $j('#jstree').jstree({
                "checkbox": {
                    "three_state": false //no cascade selection
                },
                'core': {
                    'themes': {
                        'name': 'proton',
                        'responsive': true
                    },
                    'data': this.user.allEntities
                },
                "plugins": ["checkbox", "search"]
            });
            $j('#jstree')
                // listen for event
                .on('select_node.jstree', (e: any, data: any) => {
                    if (this.mode == '') {
                        this.addEntity(data.node.id);
                    }
                }).on('deselect_node.jstree', (e: any, data: any) => {
                    this.deleteEntity(data.node.id);
                })
                // create the instance
                .jstree();

            var to: any = false;
            $j('#jstree_search').keyup(function () {
                if (to) { clearTimeout(to); }
                to = setTimeout(function () {
                    var v = $j('#jstree_search').val();
                    $j('#jstree').jstree(true).search(v);
                }, 250);
            });
        }
    }

    processAfterUpload(b64Content: any) {
        this.zone.run(() => this.resfreshUpload(b64Content));
    }

    resfreshUpload(b64Content: any) {
        if (this.signatureModel.size <= 2000000) {
            this.signatureModel.base64 = b64Content.replace(/^data:.*?;base64,/, "");
            this.signatureModel.base64ForJs = b64Content;
        } else {
            this.signatureModel.name = "";
            this.signatureModel.size = 0;
            this.signatureModel.type = "";
            this.signatureModel.base64 = "";
            this.signatureModel.base64ForJs = "";

            this.notify.error("Taille maximum de fichier dépassée (2 MB)");
        }
    }

    clickOnUploader(id: string) {
        $j('#' + id).click();
    }

    uploadSignatureTrigger(fileInput: any) {
        if (fileInput.target.files && fileInput.target.files[0]) {
            var reader = new FileReader();

            this.signatureModel.name = fileInput.target.files[0].name;
            this.signatureModel.size = fileInput.target.files[0].size;
            this.signatureModel.type = fileInput.target.files[0].type;
            if (this.signatureModel.label == "") {
                this.signatureModel.label = this.signatureModel.name;
            }

            reader.readAsDataURL(fileInput.target.files[0]);

            reader.onload = (value: any) => {
                window['angularUserAdministrationComponent'].componentAfterUpload(value.target.result);
                this.submitSignature();
            };
        }
    }

    displaySignatureEditionForm(index: number) {
        this.selectedSignature = index;
        this.selectedSignatureLabel = this.user.signatures[index].signature_label;
    }

    resetPassword(user: any) {
        let r = confirm(this.lang.confirmAction + ' ' + this.lang.resetPsw);

        if (r) {
            this.http.post(this.coreUrl + "rest/users/" + this.serialId + "/password", {})
                .subscribe((data: any) => {
                    this.notify.success(this.lang.pswReseted);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    toggleGroup(group: any) {
        if ($j('#' + group.group_id + '-input').is(':checked') == true) {
            var groupReq = {
                "groupId": group.group_id,
                "role": group.role
            };
            this.http.post(this.coreUrl + "rest/users/" + this.serialId + "/groups", groupReq)
                .subscribe((data: any) => {
                    this.user.groups = data.groups;
                    this.user.baskets = data.baskets;
                    this.notify.success(this.lang.groupAdded);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.http.delete(this.coreUrl + "rest/users/" + this.serialId + "/groups/" + group.group_id)
                .subscribe((data: any) => {
                    this.user.groups = data.groups;
                    this.user.baskets = data.baskets;
                    this.user.redirectedBaskets = data.redirectedBaskets;
                    this.notify.success(this.lang.groupDeleted);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    updateGroup(group: any) {
        this.http.put(this.coreUrl + "rest/users/" + this.serialId + "/groups/" + group.group_id, group)
            .subscribe((data: any) => {
                this.notify.success(this.lang.groupUpdated);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    addEntity(entiyId: any) {
        let entity = {
            "entityId": entiyId,
            "role": ''
        };

        this.http.post(this.coreUrl + "rest/users/" + this.serialId + "/entities", entity)
            .subscribe((data: any) => {
                this.user.entities = data.entities;
                this.user.allEntities = data.allEntities;
                this.notify.success(this.lang.entityAdded);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    updateEntity(entity: any) {
        this.http.put(this.coreUrl + "rest/users/" + this.serialId + "/entities/" + entity.entity_id, entity)
            .subscribe(() => {
                this.notify.success(this.lang.entityUpdated);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    updatePrimaryEntity(entity: any) {
        this.http.put(this.coreUrl + "rest/users/" + this.serialId + "/entities/" + entity.entity_id + "/primaryEntity", {})
            .subscribe((data: any) => {
                this.user['entities'] = data.entities;
                this.notify.success(this.lang.entityTooglePrimary + ' « ' + entity.entity_id + ' »');
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    deleteEntity(entityId: any) {

        //first check confidential state
        this.http.get(this.coreUrl + "rest/users/" + this.serialId + "/entities/" + entityId)
            .subscribe((data: any) => {
                if (!data['hasConfidentialityInstances'] && !data['hasListTemplates']) {
                    this.http.delete(this.coreUrl + "rest/users/" + this.serialId + "/entities/" + entityId)
                        .subscribe((data: any) => {
                            this.user.entities = data.entities;
                            this.user.allEntities = data.allEntities;
                            this.notify.success(this.lang.entityDeleted);
                        }, (err) => {
                            this.notify.error(err.error.errors);
                        });
                } else {
                    this.config = { data: { hasConfidentialityInstances: data['hasConfidentialityInstances'], hasListTemplates: data['hasListTemplates'] } };
                    this.dialogRef = this.dialog.open(UserAdministrationRedirectModalComponent, this.config);
                    this.dialogRef.afterClosed().subscribe((result: any) => {
                        this.mode = 'delete';
                        if (result) {
                            this.mode = result.processMode;
                            this.http.request('DELETE', this.coreUrl + "rest/users/" + this.serialId + "/entities/" + entityId, { body: { "mode": this.mode, "newUser": result.newUser } })
                                .subscribe((data: any) => {
                                    this.user.entities = data.entities;
                                    this.user.allEntities = data.allEntities;
                                    this.notify.success(this.lang.entityDeleted);
                                }, (err) => {
                                    this.notify.error(err.error.errors);
                                });
                        } else {
                            $j('#jstree').jstree('select_node', entityId);
                            this.mode = '';
                        }
                        this.dialogRef = null;
                    });
                }

            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    submitSignature() {
        this.http.post(this.coreUrl + "rest/users/" + this.serialId + "/signatures", this.signatureModel)
            .subscribe((data: any) => {
                this.user.signatures = data.signatures;
                this.notify.success(this.lang.signAdded);
                this.signatureModel = {
                    base64: "",
                    base64ForJs: "",
                    name: "",
                    type: "",
                    size: 0,
                    label: "",
                };
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    updateSignature(selectedSignature: any) {
        var id = this.user.signatures[selectedSignature].id;
        var label = this.user.signatures[selectedSignature].signature_label;

        this.http.put(this.coreUrl + "rest/users/" + this.serialId + "/signatures/" + id, { "label": label })
            .subscribe((data: any) => {
                this.user.signatures[selectedSignature].signature_label = data.signature.signature_label;
                this.notify.success(this.lang.signUpdated);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    deleteSignature(signature: any) {
        let r = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + signature.signature_label + ' »');

        if (r) {
            this.http.delete(this.coreUrl + "rest/users/" + this.serialId + "/signatures/" + signature.id)
                .subscribe((data: any) => {
                    this.user.signatures = data.signatures;
                    this.notify.success(this.lang.signDeleted);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    test(event: any) {
        if (event.mouseEvent.dataTransfer.files && event.mouseEvent.dataTransfer.files[0]) {
            var reader = new FileReader();

            this.signatureModel.name = event.mouseEvent.dataTransfer.files[0].name;
            this.signatureModel.size = event.mouseEvent.dataTransfer.files[0].size;
            this.signatureModel.type = event.mouseEvent.dataTransfer.files[0].type;
            if (this.signatureModel.label == "") {
                this.signatureModel.label = this.signatureModel.name;
            }

            reader.readAsDataURL(event.mouseEvent.dataTransfer.files[0]);

            reader.onload = (value: any) => {
                window['angularUserAdministrationComponent'].componentAfterUpload(value.target.result);
                this.submitSignature();
            };
        }
    }

    addBasketRedirection(newUser: any) {
        let basketsRedirect: any[] = [];

        this.selectionBaskets.selected.forEach((elem: any) => {
            basketsRedirect.push(
                {
                    actual_user_id: newUser.serialId,
                    basket_id: elem.basket_id,
                    group_id: elem.groupSerialId,
                    originalOwner: null
                }
            )
        });

        let r = confirm(this.lang.confirmAction + ' ' + this.lang.redirectBasket);

        if (r) {
            this.http.post(this.coreUrl + "rest/users/" + this.serialId + "/redirectedBaskets", basketsRedirect)
                .subscribe((data: any) => {
                    this.userCtrl.setValue('');
                    this.user.baskets = data["baskets"];
                    this.user.redirectedBaskets = data["redirectedBaskets"];
                    this.selectionBaskets.clear();
                    this.notify.success(this.lang.basketUpdated);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    reassignBasketRedirection(newUser: any, basket: any, i: number) {
        let r = confirm(this.lang.confirmAction + ' ' + this.lang.redirectBasket);

        if (r) {
            this.http.post(this.coreUrl + "rest/users/" + this.serialId + "/redirectedBaskets", [
                {
                    "actual_user_id": newUser.serialId,
                    "basket_id": basket.basket_id,
                    "group_id": basket.group_id,
                    "originalOwner": basket.owner_user_id,
                }
            ])
                .subscribe((data: any) => {
                    this.userCtrl.setValue('');
                    this.user.baskets = data["baskets"];
                    this.user.assignedBaskets.splice(i, 1);
                    this.notify.success(this.lang.basketUpdated);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    delBasketRedirection(basket: any, i: number) {
        let r = confirm(this.lang.confirmAction);

        if (r) {
            this.http.delete(this.coreUrl + "rest/users/" + this.serialId + "/redirectedBaskets?redirectedBasketIds[]=" + basket.id)
                .subscribe((data: any) => {
                    this.userCtrl.setValue('');
                    this.user.baskets = data["baskets"];
                    this.user.redirectedBaskets.splice(i, 1);
                    this.notify.success(this.lang.basketUpdated);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    delBasketAssignRedirection(basket: any, i: number) {
        let r = confirm(this.lang.confirmAction);

        if (r) {
            this.http.delete(this.coreUrl + "rest/users/" + this.serialId + "/redirectedBaskets?redirectedBasketIds[]=" + basket.id)
                .subscribe((data: any) => {
                    this.userCtrl.setValue('');
                    this.user.baskets = data["baskets"];
                    this.user.assignedBaskets.splice(i, 1);
                    this.notify.success(this.lang.basketUpdated);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    toggleBasket(state: boolean) {
        let basketsDisable: any = [];
        this.user.baskets.forEach((elem: any) => {
            this.selectionBaskets.selected.forEach((selected: any) => {
                if (elem.basket_id == selected.basket_id && elem.group_id == selected.group_id && elem.allowed != state) {
                    elem.allowed = state;
                    basketsDisable.push({ "basketId": elem.basket_id, "groupSerialId": elem.groupSerialId, "allowed": state });
                }
            });
        });
        if (basketsDisable.length > 0) {
            this.http.put(this.coreUrl + "rest/users/" + this.serialId + "/baskets", { "baskets": basketsDisable })
                .subscribe((data: any) => {
                    this.selectionBaskets.clear();
                    this.notify.success(this.lang.basketsUpdated);
                }, (err: any) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    activateAbsence() {
        this.http.put(this.coreUrl + "rest/users/" + this.serialId + "/status", { "status": "ABS" })
            .subscribe((data: any) => {
                this.user.status = data.user.status;
                this.userAbsenceModel = [];
                this.notify.success(this.lang.absOn);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    desactivateAbsence() {
        this.http.put(this.coreUrl + "rest/users/" + this.serialId + "/status", { "status": "OK" })
            .subscribe((data: any) => {
                this.user.status = data.user.status;
                this.notify.success(this.lang.absOff);
            }, (err: any) => {
                this.notify.error(err.error.errors);
            });
    }

    getErrorMessage() {
        if (this.firstFormGroup.controls['newPasswordCtrl'].value != this.firstFormGroup.controls['retypePasswordCtrl'].value) {
            this.firstFormGroup.controls['retypePasswordCtrl'].setErrors({ 'mismatch': true });
        } else {
            this.firstFormGroup.controls['retypePasswordCtrl'].setErrors(null);
        }
        if (this.firstFormGroup.controls['newPasswordCtrl'].hasError('required')) {
            return this.lang.requiredField + ' !';

        } else if (this.firstFormGroup.controls['newPasswordCtrl'].hasError('minlength') && this.passwordRules.minLength.enabled) {
            return this.passwordRules.minLength.value + ' ' + this.lang.passwordminLength + ' !';

        } else if (this.firstFormGroup.controls['newPasswordCtrl'].errors != null && this.firstFormGroup.controls['newPasswordCtrl'].errors.complexityUpper !== undefined && this.passwordRules.complexityUpper.enabled) {
            return this.lang.passwordcomplexityUpper + ' !';

        } else if (this.firstFormGroup.controls['newPasswordCtrl'].errors != null && this.firstFormGroup.controls['newPasswordCtrl'].errors.complexityNumber !== undefined && this.passwordRules.complexityNumber.enabled) {
            return this.lang.passwordcomplexityNumber + ' !';

        } else if (this.firstFormGroup.controls['newPasswordCtrl'].errors != null && this.firstFormGroup.controls['newPasswordCtrl'].errors.complexitySpecial !== undefined && this.passwordRules.complexitySpecial.enabled) {
            return this.lang.passwordcomplexitySpecial + ' !';

        } else {
            this.firstFormGroup.controls['newPasswordCtrl'].setErrors(null);
            this.validPassword = true;
            return '';
        }
    }

    matchValidator(group: FormGroup) {
        if (group.controls['newPasswordCtrl'].value == group.controls['retypePasswordCtrl'].value) {
            return false;
        } else {
            group.controls['retypePasswordCtrl'].setErrors({ 'mismatch': true });
            return { 'mismatch': true };
        }
    }

    regexValidator(regex: RegExp, error: ValidationErrors): ValidatorFn {
        return (control: AbstractControl): { [key: string]: any } => {
            if (!control.value) {
                return null;
            }
            const valid = regex.test(control.value);
            return valid ? null : error;
        };
    }

    changePasswd() {
        this.http.get(this.coreUrl + 'rest/passwordRules')
            .subscribe((data: any) => {
                let valArr: ValidatorFn[] = [];
                let ruleTextArr: String[] = [];
                let otherRuleTextArr: String[] = [];

                valArr.push(Validators.required);

                data.rules.forEach((rule: any) => {
                    if (rule.label == 'minLength') {
                        this.passwordRules.minLength.enabled = rule.enabled;
                        this.passwordRules.minLength.value = rule.value;
                        if (rule.enabled) {
                            valArr.push(Validators.minLength(this.passwordRules.minLength.value));
                            ruleTextArr.push(rule.value + ' ' + this.lang['password' + rule.label]);
                        }


                    } else if (rule.label == 'complexityUpper') {
                        this.passwordRules.complexityUpper.enabled = rule.enabled;
                        this.passwordRules.complexityUpper.value = rule.value;
                        if (rule.enabled) {
                            valArr.push(this.regexValidator(new RegExp('[A-Z]'), { 'complexityUpper': '' }));
                            ruleTextArr.push(this.lang['password' + rule.label]);
                        }


                    } else if (rule.label == 'complexityNumber') {
                        this.passwordRules.complexityNumber.enabled = rule.enabled;
                        this.passwordRules.complexityNumber.value = rule.value;
                        if (rule.enabled) {
                            valArr.push(this.regexValidator(new RegExp('[0-9]'), { 'complexityNumber': '' }));
                            ruleTextArr.push(this.lang['password' + rule.label]);
                        }


                    } else if (rule.label == 'complexitySpecial') {
                        this.passwordRules.complexitySpecial.enabled = rule.enabled;
                        this.passwordRules.complexitySpecial.value = rule.value;
                        if (rule.enabled) {
                            valArr.push(this.regexValidator(new RegExp('[^A-Za-z0-9]'), { 'complexitySpecial': '' }));
                            ruleTextArr.push(this.lang['password' + rule.label]);
                        }
                    } else if (rule.label == 'renewal') {
                        this.passwordRules.renewal.enabled = rule.enabled;
                        this.passwordRules.renewal.value = rule.value;
                        if (rule.enabled) {
                            otherRuleTextArr.push(this.lang['password' + rule.label] + ' <b>' + rule.value + ' ' + this.lang.days + '</b>. ' + this.lang['password2' + rule.label] + '.');
                        }
                    } else if (rule.label == 'historyLastUse') {
                        this.passwordRules.historyLastUse.enabled = rule.enabled;
                        this.passwordRules.historyLastUse.value = rule.value;
                        if (rule.enabled) {
                            otherRuleTextArr.push(this.lang['passwordhistoryLastUseDesc'] + ' <b>' + rule.value + '</b> ' + this.lang['passwordhistoryLastUseDesc2'] + '.');
                        }
                    }

                });
                this.ruleText = ruleTextArr.join(', ');
                this.otherRuleText = otherRuleTextArr.join('<br/>');
                this.firstFormGroup.controls["newPasswordCtrl"].setValidators(valArr);
            }, (err: any) => {
                this.notify.error(err.error.errors);
            });

        this.firstFormGroup = this._formBuilder.group({
            newPasswordCtrl: [
                ''
            ],
            retypePasswordCtrl: [
                '',
                Validators.compose([Validators.required])
            ],
            currentPasswordCtrl: [
                '',
                Validators.compose([Validators.required])
            ]
        }, {
                validator: this.matchValidator
            });

        this.validPassword = false;
        this.firstFormGroup.controls['currentPasswordCtrl'].setErrors(null);
        this.firstFormGroup.controls['newPasswordCtrl'].setErrors(null);
        this.firstFormGroup.controls['retypePasswordCtrl'].setErrors(null);
        this.showPassword = true;
        this.selectedTabIndex = 0;
    }

    updatePassword() {
        this.passwordModel.currentPassword = this.firstFormGroup.controls['currentPasswordCtrl'].value;
        this.passwordModel.newPassword = this.firstFormGroup.controls['newPasswordCtrl'].value;
        this.passwordModel.reNewPassword = this.firstFormGroup.controls['retypePasswordCtrl'].value;
        this.http.put(this.coreUrl + 'rest/users/' + this.serialId + '/password', this.passwordModel)
            .subscribe(() => {
                this.showPassword = false;
                this.passwordModel = {
                    currentPassword: "",
                    newPassword: "",
                    reNewPassword: "",
                };
                this.notify.success(this.lang.passwordUpdated);
            }, (err: any) => {
                this.notify.error(err.error.errors);
            });
    }

    onSubmit() {
        if (this.creationMode) {
            var r = true;

            this.http.get(this.coreUrl + "rest/users/" + this.user.userId + "/status")
                .subscribe((data: any) => {
                    var deletedUser = false;
                    if (data.status && data.status == 'DEL') {
                        r = confirm(this.lang.reactivateUserDeleted);
                        deletedUser = true;
                    }
                    if (r) {
                        this.http.post(this.coreUrl + "rest/users", this.user)
                            .subscribe((data: any) => {
                                if (deletedUser) {
                                    this.notify.success(this.lang.userUpdated);
                                } else {
                                    this.notify.success(this.lang.userAdded);
                                }
                                this.router.navigate(["/administration/users/" + data.user.id]);
                            }, (err: any) => {
                                this.notify.error(err.error.errors);
                            });
                    }
                }, () => {
                });
        } else {
            this.http.put(this.coreUrl + "rest/users/" + this.serialId, this.user)
                .subscribe((data: any) => {
                    this.notify.success(this.lang.userUpdated);
                }, (err: any) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    setUserModeLogin(event: any) {
        if (event.checked) {
            this.user.loginmode = "restMode";
        } else {
            this.user.loginmode = "standard";
        }
    }

    sendToMaarchParapheur() {
        let r = confirm(this.lang.confirmAction + ' ' + this.lang.createUserInMaarchParapheur);

        if (r) {
            this.http.put(this.coreUrl + "rest/users/" + this.serialId + '/maarchParapheur', '')
                .subscribe((data: any) => {
                    this.notify.success(this.lang.userCreatedInMaarchParapheur);
                    this.user.external_id['maarchParapheur'] = data.externalId;
                    this.user.canCreateMaarchParapheurUser = false;
                }, (err: any) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    setLowerUserId() {
        this.user.userId = this.user.userId.toLowerCase();
    }

    syncMP() {
        this.loadingSign = true;

        this.http.put('../../rest/users/' + this.user.id + '/externalSignatures', {})
            .subscribe((data: any) => {
                this.loadingSign = false;
                this.notify.success(this.lang.signsSynchronized);
            }, (err) => {
                this.loadingSign = false;
                this.notify.error(err.error.errors);
            });
    }
}

@Component({
    templateUrl: "user-administration-redirect-modal.component.html",
    styles: [".mat-dialog-content{max-height: 65vh;width:600px;}"]
})
export class UserAdministrationRedirectModalComponent extends AutoCompletePlugin {
    lang: any = LANG;

    redirectUser: String = '';
    processMode: String = '';

    constructor(public http: HttpClient, @Inject(MAT_DIALOG_DATA) public data: any, public dialogRef: MatDialogRef<UserAdministrationRedirectModalComponent>) {
        super(http, ['users']);
    }
}
