import {CommonActions, createNavigationContainerRef} from '@react-navigation/native';

import {AuthNav, StackNav} from './navigationKeys';

export const navigationRef = createNavigationContainerRef();

export function resetToLogin() {
  if (!navigationRef.isReady()) return;

  navigationRef.dispatch(
    CommonActions.reset({
      index: 0,
      routes: [
        {
          name: StackNav.AuthNavigation,
          params: {screen: AuthNav.TipoUsuario},
        },
      ],
    }),
  );
}

