import { isObject } from '@vue/shared';

export default class UtilService {
	static message(message) {
		let msg = '';

		if (isObject(message.errors)) {
			msg += '<ul class="py-0 pl-3 mx-0 my-0">';
			for (const element in message.errors) {
				msg += `<li class="mx-0 my-0 px-0 py-0">${message.errors[element]}</li>`;
			}
			msg += '</ul>';
		} else {
			msg = message?.message;
		}

		return msg;
	}
}
