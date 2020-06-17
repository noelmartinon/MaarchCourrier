import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { NotificationService } from '../../notification.service';

@Component({
  selector: 'app-useradmin',
  templateUrl: './useradmin.component.html',
  styleUrls: ['./useradmin.component.scss']
})
export class UseradminComponent implements OnInit {

  stepFormGroup: FormGroup;

  constructor(
      private _formBuilder: FormBuilder,
      private notify: NotificationService,
  ) {
      this.stepFormGroup = this._formBuilder.group({
        login: ['superadmin', Validators.required],
        password: ['', Validators.required],
        email: ['dev@maarch.org', Validators.required],
    });
  }

  ngOnInit(): void {
  }

  isValidStep() {
      return this.stepFormGroup === undefined ? false : this.stepFormGroup.valid;
  }

  getFormGroup() {
      return this.stepFormGroup;
  }

  checkAvailability() {
      this.stepFormGroup.controls['firstCtrl'].setValue('success');
      this.notify.success('Le chemin est disponible');
  }

}
