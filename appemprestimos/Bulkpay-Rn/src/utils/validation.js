import strings from '../i18n/strings';

const emailRegex = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w\w+)+$/;

const emailPassRegex =
  /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

const nameRegex = /^[a-zA-Z ]{2,40}$/;

const cardNumberRegex = /^[0-9]{16}$/;

const expiryDate = /^(0[1-9]|1[0-2])\/?(([0-9]{4}|[0-9]{2})$)/

const cvvRegex = /^[0-9]{3}$/;

const validateEmail = email => {
  if (!email) {
    return {
      status: false,
      msg: strings.thisFieldIsMandatory,
    };
  } else {
    return emailRegex.test(email)
      ? {status: true, msg: ''}
      : {
          status: false,
          msg: strings.validEmail,
        };
  }
};

const validatePassword = password => {
  if (!password) {
    return {
      status: false,
      msg: strings.thisFieldIsMandatory,
    };
  } else {
    return emailPassRegex.test(password)
      ? {status: true, msg: ''}
      : {
          status: false,
          msg: strings.validPass,
        };
  }
};

const validateName = name => {
  if (!name) {
    return {
      status: false,
      msg: strings.thisFieldIsMandatory,
    };
  } else {
    return nameRegex.test(name)
      ? {status: true, msg: ''}
      : {
          status: false,
          msg: strings.validName,
        };
  }
};

const atmCardNumberRegex = number => {
  if (!number) {
    return {
      status: false,
      msg: strings.thisFieldIsMandatory,
    };
  } else {
    return cardNumberRegex.test(number)
      ? {status: true, msg: ''}
      : {
          status: false,
          msg: strings.validCardNumber,
        };
  }
};

const expiryDateValidation = date => {
  if (!date) {
    return {
      status: false,
      msg: strings.thisFieldIsMandatory,
    };
  } else {
    return expiryDate.test(date)
      ? {status: true, msg: ''}
      : {
          status: false,
          msg: strings.validExpiryDate,
        };
  }
};

const vccNumber = vcc => {
  if (!vcc) {
    return {
      status: false,
      msg: strings.thisFieldIsMandatory,
    };
  } else {
    return cvvRegex.test(vcc)
      ? {status: true, msg: ''}
      : {
          status: false,
          msg: strings.validVcc,
        };
  }
};

export {
  validateEmail,
  validatePassword,
  validateName,
  atmCardNumberRegex,
  expiryDateValidation,
  vccNumber,
};
