import React from 'react'
import { NavigationContainer } from '@react-navigation/native'
import StackNavigation from './type/StackNavigation'
import {navigationRef} from './rootNavigation'

export default function AppNavigator() {
  return (
    <NavigationContainer ref={navigationRef}>
      <StackNavigation />
    </NavigationContainer>
  )
}