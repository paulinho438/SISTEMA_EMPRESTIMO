import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';

// Local imports
import {AuthNav} from '../navigationKeys';
import {AuthRoute} from '../navigationRoute';
import CBackButton from '../../components/common/CBackButton';
import {StyleSheet} from 'react-native';
import {styles} from '../../themes';
import {moderateScale} from '../../common/constant';

const Stack = createNativeStackNavigator();

const AuthNavigation = ({navigation}) => {
  return (
    <Stack.Navigator>
      <Stack.Screen
        name={AuthNav.SignIn}
        component={AuthRoute.SignIn}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.PassRecovery}
        component={AuthRoute.PassRecovery}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.OtpAuth}
        component={AuthRoute.OtpAuth}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.VerifyIdentity}
        component={AuthRoute.VerifyIdentity}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.CreatePass}
        component={AuthRoute.CreatePass}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.SignUp}
        component={AuthRoute.SignUp}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.CountryRes}
        component={AuthRoute.CountryRes}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.Reasons}
        component={AuthRoute.Reasons}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.CreatePin}
        component={AuthRoute.CreatePin}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.FaceIdentity}
        component={AuthRoute.FaceIdentity}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.ProofRes}
        component={AuthRoute.ProofRes}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.CardOnBoarding}
        component={AuthRoute.CardOnBoarding}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.CardStyle}
        component={AuthRoute.CardStyle}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={AuthNav.NewCard}
        component={AuthRoute.NewCard}
        options={{headerShown: false}}
      />
    </Stack.Navigator>
  );
};

const localStyle = StyleSheet.create({
  CBackButton: {
    ...styles.mb15,
    bottom: moderateScale(10),
  },
});
export default AuthNavigation;
